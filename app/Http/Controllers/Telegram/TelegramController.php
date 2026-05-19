<?php

namespace App\Http\Controllers\Telegram;

use App\Models\Admin;
use App\Models\BotUser;
use App\Models\Product;
use App\Models\PromotionSetting;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Services\SupportForumService;
use Throwable;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramController
{
    protected Api $telegram;
    protected SupportForumService $forum;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $this->forum = new SupportForumService();
    }

    private function t($user, $key, $replace = [])
    {
        app()->setLocale($user->lang ?? 'ru');

        return __($key, $replace);
    }

    private function showLangMenu($chatId, $user = null)
    {
        if ($user) {
            app()->setLocale($user->lang ?? 'ru');
        }

        $keyboard = Keyboard::make([
            'keyboard' => [
                [['text' => '🇷🇺 Русский']],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ]);

        $text = $user
            ? $this->t($user, 'bot.change_language')
            : __('bot.choose_language');

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $keyboard,
        ]);
    }

    public function handleWebhook(): void
    {
        $update = $this->telegram->getWebhookUpdates();
        $this->telegram->commandsHandler(true);

        // === Inline Query (поиск товара для пересылки) ===
        if ($update->has('inline_query')) {
            $this->handleInlineQuery($update->get('inline_query'));
            return;
        }

        if (! $update->has('message')) {
            return;
        }

        $message = $update->getMessage();
        $chatId = $message->getChat()->getId();
        $chatType = (string) $message->getChat()->getType();

        // === Сообщение из менеджерской супергруппы (forum topic) ===
        $supportGroupId = config('services.support.group_id');
        if ($supportGroupId && (string) $chatId === (string) $supportGroupId) {
            try {
                $this->handleManagerForumMessage($message);
            } catch (Throwable $e) {
                \Log::error('[support_forum] manager reply handler упал', [
                    'error' => $e->getMessage(),
                ]);
            }
            return;
        }

        // === Любое сообщение из группы/супергруппы — не пользователь, не обрабатываем как бота ===
        if (in_array($chatType, ['group', 'supergroup'], true)) {
            $chatTitle = (string) $message->getChat()->getTitle();
            \Log::warning('[support_forum] получено сообщение из группы, которая не совпадает с TELEGRAM_SUPPORT_GROUP_ID', [
                'this_chat_id'   => $chatId,
                'this_chat_title'=> $chatTitle,
                'this_chat_type' => $chatType,
                'is_forum'       => (bool) data_get($message, 'chat.is_forum', false),
                'configured_id'  => $supportGroupId,
                'hint'           => 'Если это ваша группа поддержки — пропишите TELEGRAM_SUPPORT_GROUP_ID=' . $chatId,
            ]);
            return;
        }

        $text = trim((string) $message->getText());
        if ($text === '') {
            $caption = trim((string) data_get($message, 'caption', ''));
            if ($caption !== '') {
                $text = $caption;
            }
        }
        $username = $message->getChat()->getUsername();

        $user = BotUser::firstOrCreate(
            ['chat_id' => $chatId],
            [
                'uname' => $username,
                'step' => 'ask_phone',
                'lang' => 'ru',
                'is_active' => true,
            ]
        );

        if (! $user->is_active) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.account_inactive', ['admin' => '@exampleAdmin']),
            ]);

            return;
        }

        /**
         * ===========================
         * ВЫБОР ЯЗЫКА ПРИ ВХОДЕ
         * ===========================
         */
        if ($text === '/start') {
            $user->update(['step' => 'ask_phone', 'lang' => 'ru']);
            app()->setLocale('ru');
            $this->sendPhoneRequest($chatId, $user);

            return;
        }

        // Deep link: /start product_123 — отправляем карточку товара
        if (preg_match('/^\/start\s+product_(\d+)$/', $text, $startMatch)) {
            $this->sendProductCard($chatId, (int) $startMatch[1], $user);

            return;
        }

        // Legacy: if user stuck on choose_lang, redirect to ask_phone
        if ($user->step === 'choose_lang') {
            $user->update(['step' => 'ask_phone', 'lang' => 'ru']);
            $this->sendPhoneRequest($chatId, $user);

            return;
        }

        /**
         * ===========================
         * РЕГИСТРАЦИЯ
         * ===========================
         */
        // Совместимость со старыми пользователями, которые остались на удалённых шагах.
        if ($user->step === 'ask_first_name' || $user->step === 'ask_second_name') {
            $user->update(['step' => 'ask_phone']);
            $this->sendPhoneRequest($chatId, $user);

            return;
        }

        if ($message->has('contact') && $user->step === 'ask_phone') {

            $phone = $message->getContact()->getPhoneNumber();

            $user->update([
                'phone' => $phone,
                'step' => 'done',
                'first_name' => $user->first_name ?: ($user->uname ?: null),
            ]);

            $this->sendMainMenu($chatId, $user);

            return;
        }

        if ($user->step === 'ask_phone') {

            $clean = preg_replace('/\D/', '', $text);

            if (! is_numeric($clean)) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $this->t($user, 'bot.phone_invalid'),
                ]);

                return;
            }

            $user->update([
                'phone' => $clean,
                'step' => 'done',
                'first_name' => $user->first_name ?: ($user->uname ?: null),
            ]);

            $this->sendMainMenu($chatId, $user);

            return;
        }

        /**
         * ===========================
         * ИЗМЕНЕНИЕ ЯЗЫКА В НАСТРОЙКАХ
         * ===========================
         */
        // Изменить имя
        if ($user->step === 'profile_menu' && $text === $this->t($user, 'bot.menu.edit_first')) {
            $user->update(['step' => 'edit_first']);
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.enter_new_first'),
            ]);

            return;
        }

        // Изменить фамилию
        if ($user->step === 'profile_menu' && $text === $this->t($user, 'bot.menu.edit_last')) {
            $user->update(['step' => 'edit_last']);
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.enter_new_last'),
            ]);

            return;
        }

        // Изменить телефон
        if ($user->step === 'profile_menu' && $text === $this->t($user, 'bot.menu.edit_phone')) {
            $user->update(['step' => 'edit_phone']);

            $keyboard = Keyboard::make([
                'keyboard' => [
                    [
                        [
                            'text' => $this->t($user, 'bot.send_phone_button'),
                            'request_contact' => true,
                        ],
                    ],
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.send_phone'),
                'reply_markup' => $keyboard,
            ]);

            return;
        }

        if ($user->step === 'edit_first') {
            $user->update([
                'first_name' => $text,
                'step' => 'profile_menu',
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.data_updated'),
            ]);

            $this->showProfileMenu($chatId, $user);

            return;
        }

        if ($user->step === 'edit_last') {
            $user->update([
                'second_name' => $text,
                'step' => 'profile_menu',
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.data_updated'),
            ]);

            $this->showProfileMenu($chatId, $user);

            return;
        }

        if ($message->has('contact') && $user->step === 'edit_phone') {
            $phone = $message->getContact()->getPhoneNumber();

            $user->update([
                'phone' => $phone,
                'step' => 'profile_menu',
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.data_updated'),
            ]);

            $this->showProfileMenu($chatId, $user);

            return;
        }

        if ($user->step === 'change_lang') {

            $newLang = null;
            if ($text === '🇷🇺 Русский') {
                $newLang = 'ru';
            }

            if ($newLang) {
                $user->update(['lang' => $newLang, 'step' => 'profile_menu']);
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $this->t($user, 'bot.language_changed'),
                ]);
                $this->showProfileMenu($chatId, $user);

                return;
            } else {
                $this->showLangMenu($chatId, $user);

                return;
            }
        }

        // ✅ КНОПКА "ИЗМЕНИТЬ ЯЗЫК"
        if ($user->step === 'profile_menu' && $text === $this->t($user, 'bot.menu.change_language')) {
            $user->update(['step' => 'change_lang']);
            $this->showLangMenu($chatId, $user);

            return;
        }

        /**
         * ===========================
         * МЕНЮ
         * ===========================
         */
        if ($text === $this->t($user, 'bot.menu.orders')) {
            $this->sendOrdersButton($chatId, $user);

            return;
        }

        if ($text === $this->t($user, 'bot.menu.profile')) {

            $this->showProfileMenu($chatId, $user);
            $user->update(['step' => 'profile_menu']);

            return;
        }

        if ($text === $this->t($user, 'bot.menu.shop')) {
            $this->sendShopButton($chatId, $user);

            return;
        }

        if ($text === $this->t($user, 'bot.menu.back')) {
            $user->update(['step' => 'done']);
            $this->sendMainMenu($chatId, $user);

            return;
        }

        /**
         * ===========================
         * ЧАТ С МЕНЕДЖЕРОМ
         * ===========================
         */
        if ($text === $this->t($user, 'bot.menu.manager')) {

            // Общий чат с поддержкой — один на пользователя (order_id = NULL)
            $chat = SupportChat::firstOrCreate(
                ['bot_user_id' => $user->id, 'order_id' => null],
                ['status' => 'new']
            );

            $chat->update(['status' => 'open']);

            $user->update([
                'step' => 'chat_with_manager_first_message',
                'current_chat_id' => $chat->id,
            ]);

            $keyboard = Keyboard::make([
                'keyboard' => [
                    [['text' => $this->t($user, 'bot.end_chat')]],
                ],
                'resize_keyboard' => true,
            ]);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.chat_connected'),
                'reply_markup' => $keyboard,
            ]);

            return;
        }

        if ($text === $this->t($user, 'bot.end_chat') || $text === '⏹ Завершить чат') {

            $chat = null;
            if ($user->current_chat_id) {
                $chat = SupportChat::find($user->current_chat_id);
            }

            // Если чат привязан к заказу — закрывать может только менеджер
            if ($chat && $chat->order_id) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "🔒 Чат по заказу №{$chat->order_id} может закрыть только менеджер.",
                ]);

                return;
            }

            if ($chat) {
                $chat->update(['status' => 'closed']);
                $this->forum->notifyClientClosed($chat);
                $this->forum->closeTopic($chat);
            }

            $user->update(['step' => 'done', 'current_chat_id' => null]);

            $this->sendMainMenu($chatId, $user, $this->t($user, 'bot.chat_ended'));

            return;
        }

        if ($user->step === 'chat_with_manager_first_message' || $user->step === 'chat_with_manager') {

            // Сообщение идёт в текущий активный чат (может быть общий или по заказу)
            $chat = null;
            if ($user->current_chat_id) {
                $chat = SupportChat::find($user->current_chat_id);
            }

            // Фолбек: общий чат с поддержкой
            if (! $chat) {
                $chat = SupportChat::firstOrCreate(
                    ['bot_user_id' => $user->id, 'order_id' => null],
                    ['status' => 'open']
                );
                $user->update(['current_chat_id' => $chat->id]);
            }

            $photoUrl = $this->extractPhotoUrl($message);
            $document = $this->extractDocument($message);

            // Определяем текст и вложение для сохранения
            $fileUrl = $photoUrl ?: ($document['url'] ?? null);
            $fileName = $document['file_name'] ?? null;
            $fileMime = $document['mime_type'] ?? null;

            if ($text !== '') {
                $messageText = $text;
            } elseif ($photoUrl) {
                $messageText = '📷 Фото';
            } elseif ($document) {
                $ext = $document['file_name'] ? strtoupper(pathinfo($document['file_name'], PATHINFO_EXTENSION) ?: '') : '';
                $icon = str_contains(strtolower($document['mime_type'] ?? ''), 'pdf') ? '📄' : '📎';
                $messageText = trim("{$icon} {$ext} {$document['file_name']}");
            } else {
                $messageText = '';
            }

            if ($messageText === '' && ! $fileUrl) {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $this->t($user, 'bot.unknown'),
                ]);

                return;
            }

            $msgData = [
                'chat_id' => $chat->id,
                'admin_id' => null,
                'is_from_user' => true,
                'text' => $messageText,
                'photo_url' => $fileUrl,
                'source' => 'bot',
                'source_order_id' => $chat->order_id,
            ];

            // file_name / file_mime — только если колонки уже добавлены в БД
            if (\Schema::hasColumn('support_messages', 'file_name')) {
                $msgData['file_name'] = $fileName;
            }
            if (\Schema::hasColumn('support_messages', 'file_mime')) {
                $msgData['file_mime'] = $fileMime;
            }

            try {
                SupportMessage::create($msgData);
            } catch (Throwable $e) {
                \Log::error('[support_chat] не удалось сохранить сообщение от клиента', [
                    'chat_id' => $chat->id,
                    'has_photo' => (bool) $photoUrl,
                    'has_document' => (bool) $document,
                    'error' => $e->getMessage(),
                ]);
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => '⚠️ Не удалось сохранить сообщение, попробуйте ещё раз.',
                ]);
                return;
            }

            $chat->update(['status' => 'open']);
            $user->update(['step' => 'chat_with_manager']);

            // === Форвард сообщения в менеджерскую тему (forum topic) ===
            // Берём локальный путь (тот же, что сохраняли в storage)
            $localPath = $this->urlToStoragePath($fileUrl);
            $this->forum->forwardClientMessage(
                $chat->fresh()->load('user', 'order'),
                $text,
                $localPath,
                $fileMime,
                (bool) $photoUrl,
                $fileName
            );

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.message_accepted'),
            ]);

            return;
        }

        /**
         * НЕИЗВЕСТНЫЙ ТЕКСТ
         */
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $this->t($user, 'bot.unknown'),
        ]);
    }

    /**
     * ===========================
     * ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     * ===========================
     */
    private function sendMainMenu($chatId, $user, ?string $customText = null)
    {
        $displayName = $user->first_name ?: ($user->uname ?: 'User');

        $menu = Keyboard::make([
            'keyboard' => [
                [['text' => $this->t($user, 'bot.menu.orders')]],
                [['text' => $this->t($user, 'bot.menu.profile')]],
                [['text' => $this->t($user, 'bot.menu.manager')]],
                [['text' => $this->t($user, 'bot.menu.shop')]],
            ],
            'resize_keyboard' => true,
        ]);

        $text = $customText ?? $this->t($user, 'bot.thanks', ['name' => $displayName]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $menu,
        ]);
    }

    private function sendPhoneRequest($chatId, $user): void
    {
        $keyboard = Keyboard::make([
            'keyboard' => [
                [
                    [
                        'text' => $this->t($user, 'bot.send_phone_button'),
                        'request_contact' => true,
                    ],
                ],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $this->t($user, 'bot.send_phone'),
            'reply_markup' => $keyboard,
        ]);
    }

    private function sendOrdersButton($chatId, $user)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $this->t($user, 'bot.opening_orders'),
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => $this->t($user, 'bot.my_orders'),
                            'web_app' => [
                                'url' => env('WEBAPP_URL') . "/telegram/webapp/profile?chat_id=$chatId",
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    private function showProfileMenu($chatId, $user)
    {
        $text = $this->t($user, 'bot.profile', [
            'first' => $user->first_name,
            'last' => $user->second_name,
            'phone' => $user->phone,
            'lang' => strtoupper($user->lang),
        ]);

        $keyboard = Keyboard::make([
            'keyboard' => [
                [['text' => $this->t($user, 'bot.menu.edit_first')]],
                [['text' => $this->t($user, 'bot.menu.edit_last')]],
                [['text' => $this->t($user, 'bot.menu.edit_phone')]],
                [['text' => $this->t($user, 'bot.menu.change_language')]],
                [['text' => $this->t($user, 'bot.menu.back')]],
            ],
            'resize_keyboard' => true,
        ]);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $keyboard,
        ]);
    }

    private function sendShopButton($chatId, $user)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $this->t($user, 'bot.opening_shop'),
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => $this->t($user, 'bot.go_to_shop'),
                            'web_app' => [
                                'url' => env('WEBAPP_URL') . "/telegram/webapp?chat_id=$chatId",
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    /**
     * Отправка карточки товара (фото + название + цена + кнопка «Подробнее»)
     * Вызывается при deep link: /start product_123
     */
    private function sendProductCard($chatId, int $productId, $user): void
    {
        $product = Product::with(['images', 'category'])->find($productId);

        if (! $product) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->t($user, 'bot.product_not_found'),
            ]);

            // Показываем главное меню если пользователь зарегистрирован
            if ($user->step === 'done') {
                $this->sendMainMenu($chatId, $user);
            }

            return;
        }

        // Расчёт цены со скидкой
        $promotion = PromotionSetting::query()->first();
        $productDiscount = (int) ($product->discount_percent ?? 0);
        $promoType = $promotion?->active_type ?? null;
        $promoPercent = (int) ($promotion?->discount_percent ?? 0);
        $finalPrice = (float) $product->price;

        if ($productDiscount > 0) {
            $finalPrice = $product->price * (100 - $productDiscount) / 100;
        } elseif ($promoType === PromotionSetting::TYPE_PERCENT && $promoPercent > 0) {
            $finalPrice = $product->price * (100 - $promoPercent) / 100;
        }

        $priceText = number_format($finalPrice, 0, '.', ' ') . ' ₽';

        // Формируем подпись
        $caption = $product->name . "\n";
        $caption .= $this->t($user, 'bot.price_label') . ': ' . $priceText;

        if ($product->category) {
            $caption .= "\n📦 " . $product->category->name;
        }

        // Кнопка «Подробнее» — deep link в бота, бот отправит карточку товара
        $botUsername = env('TELEGRAM_BOT_USERNAME', 'alyans_distributions_bot');
        $deepLink = "https://t.me/{$botUsername}?start=product_{$product->id}";
        $buttonText = $this->t($user, 'bot.more_details');

        $inlineKeyboard = json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => $buttonText ?: 'Подробнее',
                        'url' => $deepLink,
                    ],
                ],
            ],
        ]);

        // Отправляем фото с подписью и кнопкой
        $image = $product->images->first();

        if ($image) {
            $photoPath = storage_path('app/public/' . $image->url);

            try {
                if (file_exists($photoPath)) {
                    $this->telegram->sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => new \Telegram\Bot\FileUpload\InputFile($photoPath),
                        'caption' => $caption,
                        'reply_markup' => $inlineKeyboard,
                    ]);
                } else {
                    // Файл не на диске — попробуем через URL
                    $this->telegram->sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => asset('storage/' . $image->url),
                        'caption' => $caption,
                        'reply_markup' => $inlineKeyboard,
                    ]);
                }
            } catch (Throwable $e) {
                report($e);
                // Фоллбэк: отправляем текстом
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $caption,
                    'reply_markup' => $inlineKeyboard,
                ]);
            }
        } else {
            // Нет фото — отправляем текстом с кнопкой
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $caption,
                'reply_markup' => $inlineKeyboard,
            ]);
        }
    }

    private function extractPhotoUrl($message): ?string
    {
        $photos = [];

        if (is_object($message) && method_exists($message, 'getPhoto')) {
            $photos = $message->getPhoto() ?? [];
        }

        if (empty($photos) && is_object($message) && method_exists($message, 'get')) {
            $photos = $message->get('photo') ?? [];
        }

        if (empty($photos)) {
            $photos = data_get($message, 'photo', []);
        }

        if ($photos instanceof \Traversable) {
            $photos = iterator_to_array($photos);
        }

        if (! is_array($photos) || empty($photos)) {
            return null;
        }

        usort($photos, function ($left, $right) {
            $leftSize = (int) data_get($left, 'file_size', 0);
            $rightSize = (int) data_get($right, 'file_size', 0);

            if (is_object($left) && method_exists($left, 'getFileSize')) {
                $leftSize = (int) $left->getFileSize();
            }

            if (is_object($right) && method_exists($right, 'getFileSize')) {
                $rightSize = (int) $right->getFileSize();
            }

            return $rightSize <=> $leftSize;
        });

        $largestPhoto = $photos[0] ?? null;
        $fileId = data_get($largestPhoto, 'file_id');

        if (! $fileId && is_object($largestPhoto) && method_exists($largestPhoto, 'getFileId')) {
            $fileId = $largestPhoto->getFileId();
        }

        if (! $fileId) {
            return null;
        }

        // Качаем файл на свой сервер (Telegram URL живёт ~1 час)
        return $this->downloadTelegramFile($fileId, 'jpg');
    }

    /**
     * Скачивает файл из Telegram на свой сервер и возвращает публичный URL.
     */
    private function downloadTelegramFile(string $fileId, string $defaultExt = 'bin'): ?string
    {
        try {
            $file = $this->telegram->getFile(['file_id' => $fileId]);

            $filePath = data_get($file, 'file_path');
            if (! $filePath && is_object($file) && method_exists($file, 'getFilePath')) {
                $filePath = $file->getFilePath();
            }

            if (! $filePath) {
                \Log::warning('[telegram_file] нет file_path', ['file_id' => $fileId]);
                return null;
            }

            $remoteUrl = sprintf(
                'https://api.telegram.org/file/bot%s/%s',
                env('TELEGRAM_BOT_TOKEN'),
                $filePath
            );

            $contents = @file_get_contents($remoteUrl);
            if ($contents === false) {
                \Log::warning('[telegram_file] не удалось скачать', [
                    'file_id' => $fileId,
                    'url' => $remoteUrl,
                ]);
                return null;
            }

            // Имя файла: используем хвост file_path (там уже ext) или генерим
            $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: $defaultExt;
            $localName = 'support_files/' . uniqid('tg_', true) . '.' . $ext;

            \Illuminate\Support\Facades\Storage::disk('public')->put($localName, $contents);

            \Log::info('[telegram_file] сохранено локально', [
                'file_id' => $fileId,
                'local' => $localName,
                'size' => strlen($contents),
            ]);

            return asset('storage/' . $localName);
        } catch (Throwable $e) {
            \Log::error('[telegram_file] ошибка скачивания', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Извлекает URL и метаданные документа из сообщения Telegram (PDF, Word и т.п.)
     * Возвращает ['url' => ..., 'file_name' => ..., 'mime_type' => ...] или null.
     */
    private function extractDocument($message): ?array
    {
        $document = null;

        if (is_object($message) && method_exists($message, 'getDocument')) {
            $document = $message->getDocument();
        }

        if (! $document && is_object($message) && method_exists($message, 'get')) {
            $document = $message->get('document');
        }

        if (! $document) {
            $document = data_get($message, 'document');
        }

        if (! $document) {
            return null;
        }

        $fileId = data_get($document, 'file_id');
        if (! $fileId && is_object($document) && method_exists($document, 'getFileId')) {
            $fileId = $document->getFileId();
        }

        if (! $fileId) {
            return null;
        }

        $fileName = data_get($document, 'file_name');
        if (! $fileName && is_object($document) && method_exists($document, 'getFileName')) {
            $fileName = $document->getFileName();
        }

        $mimeType = data_get($document, 'mime_type');
        if (! $mimeType && is_object($document) && method_exists($document, 'getMimeType')) {
            $mimeType = $document->getMimeType();
        }

        $defaultExt = $fileName ? (pathinfo($fileName, PATHINFO_EXTENSION) ?: 'bin') : 'bin';
        $localUrl = $this->downloadTelegramFile($fileId, $defaultExt);

        if (! $localUrl) {
            return null;
        }

        return [
            'url' => $localUrl,
            'file_name' => $fileName ?: 'document',
            'mime_type' => $mimeType ?: 'application/octet-stream',
        ];
    }

    // ===== INLINE QUERY: поиск товара для пересылки =====
    private function handleInlineQuery($inlineQuery): void
    {
        $queryId = data_get($inlineQuery, 'id');
        $queryText = trim((string) data_get($inlineQuery, 'query', ''));
        $fromId = data_get($inlineQuery, 'from.id');

        // Парсим product_id из запроса (формат: "product_123")
        $productId = null;
        if (preg_match('/^product_(\d+)$/', $queryText, $m)) {
            $productId = (int) $m[1];
        }

        if (! $productId) {
            // Если нет product_id — ищем по тексту (умный поиск)
            $products = Product::query()
                ->where('is_active', 1)
                ->whereHas('stock', fn($q) => $q->where('quantity', '>=', 1))
                ->when($queryText, function ($q) use ($queryText) {
                    \App\Support\ProductSearch::apply($q, $queryText);
                })
                ->with(['images', 'category'])
                ->limit(20)
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $product = Product::with(['images', 'category'])->find($productId);
            $products = $product ? collect([$product]) : collect();
        }

        $promotion = PromotionSetting::query()->first();
        $lang = 'ru';
        $user = BotUser::where('chat_id', $fromId)->first();
        if ($user) {
            $lang = $user->lang ?? 'ru';
        }
        app()->setLocale($lang);

        $results = [];

        foreach ($products as $product) {
            $productDiscount = (int) ($product->discount_percent ?? 0);
            $promoType = $promotion?->active_type ?? null;
            $promoPercent = (int) ($promotion?->discount_percent ?? 0);
            $finalPrice = $product->price;

            if ($productDiscount > 0) {
                $finalPrice = $product->price * (100 - $productDiscount) / 100;
            } elseif ($promoType === PromotionSetting::TYPE_PERCENT && $promoPercent > 0) {
                $finalPrice = $product->price * (100 - $promoPercent) / 100;
            }

            $priceText = number_format($finalPrice, 0, '.', ' ') . ' ₽';
            $caption = $product->name . "\n\n💰 " . $priceText;

            if ($product->category) {
                $caption .= "\n📦 " . $product->category->name;
            }

            $webappUrl = env('WEBAPP_URL') . "/telegram/webapp/product/show/{$product->id}";

            $buttonText = __('bot.more_details', [], $lang) ?: 'Подробнее';

            $image = $product->images->first();
            $imageUrl = $image
                ? asset('storage/' . $image->url)
                : (env('APP_URL') . '/no-image.png');

            $results[] = [
                'type' => 'article',
                'id' => (string) $product->id,
                'title' => $product->name,
                'description' => $priceText . ($product->category ? ' • ' . $product->category->name : ''),
                'thumb_url' => $imageUrl,
                'input_message_content' => [
                    'message_text' => $caption,
                    'parse_mode' => 'HTML',
                ],
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => $buttonText,
                                'web_app' => ['url' => $webappUrl],
                            ],
                        ],
                    ],
                ],
            ];
        }

        try {
            $this->telegram->answerInlineQuery([
                'inline_query_id' => $queryId,
                'results' => json_encode($results),
                'cache_time' => 10,
                'is_personal' => true,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /* =========================================================================
     | МЕНЕДЖЕРСКАЯ ГРУППА (FORUM TOPICS)
     * =======================================================================*/

    /**
     * Обработка сообщения, пришедшего в супергруппу менеджеров.
     * Только из темы, привязанной к SupportChat. Ответы пересылаются клиенту.
     */
    private function handleManagerForumMessage($message): void
    {
        // Игнорируем системные апдейты (новый участник, закрытие темы и т.п.)
        // — у них нет from/text/photo/document/caption
        $from = $this->msgFrom($message);
        if (!$from) {
            return;
        }

        // Не реагируем на сообщения самого бота
        $isBot = (bool) data_get($from, 'is_bot', false);
        if ($isBot) {
            return;
        }

        // message_thread_id — обязательный признак сообщения в теме
        $topicId = (int) (data_get($message, 'message_thread_id', 0)
            ?: data_get($message, 'reply_to_message.message_thread_id', 0));
        if ($topicId <= 0) {
            return;
        }

        $chat = SupportChat::where('telegram_topic_id', $topicId)->with('user', 'order')->first();
        if (!$chat) {
            return;
        }

        $managerTgId = (int) data_get($from, 'id');
        $managerUname = (string) data_get($from, 'username', '');
        $managerFirstName = trim((string) data_get($from, 'first_name', ''));
        $managerName = $managerUname !== '' ? '@' . $managerUname : ($managerFirstName ?: 'Менеджер');

        $text = trim((string) data_get($message, 'text', ''));
        $caption = trim((string) data_get($message, 'caption', ''));
        if ($text === '' && $caption !== '') {
            $text = $caption;
        }

        // === Служебные команды в теме ===
        if (str_starts_with($text, '/close')) {
            $this->handleTopicCloseCommand($chat);
            return;
        }
        if (str_starts_with($text, '/info')) {
            $this->forum->sendInfoCard($chat);
            return;
        }

        // Маппинг telegram_user_id → admin_id (если менеджер привязан)
        $admin = $managerTgId
            ? Admin::where('telegram_user_id', $managerTgId)->first()
            : null;

        // === Извлекаем медиа ===
        $photoUrl = $this->extractPhotoUrl($message);
        $document = $this->extractDocument($message);

        $fileUrl = $photoUrl ?: ($document['url'] ?? null);
        $fileName = $document['file_name'] ?? null;
        $fileMime = $document['mime_type'] ?? null;
        $isImage = (bool) $photoUrl;
        $localPath = $this->urlToStoragePath($fileUrl);

        if ($text === '' && !$fileUrl) {
            return; // пустое или непонятное сообщение — игнор
        }

        // === Сохраняем в БД ===
        $messageText = $text;
        if ($messageText === '' && $isImage) {
            $messageText = '📷 Фото';
        } elseif ($messageText === '' && $document) {
            $ext = $document['file_name'] ? strtoupper(pathinfo($document['file_name'], PATHINFO_EXTENSION) ?: '') : '';
            $icon = str_contains(strtolower($document['mime_type'] ?? ''), 'pdf') ? '📄' : '📎';
            $messageText = trim("{$icon} {$ext} {$document['file_name']}");
        }

        $msgData = [
            'chat_id' => $chat->id,
            'admin_id' => $admin?->id,
            'is_from_user' => false,
            'text' => $messageText,
            'photo_url' => $fileUrl,
            'source' => 'support',
            'source_order_id' => $chat->order_id,
        ];
        if (\Schema::hasColumn('support_messages', 'file_name')) {
            $msgData['file_name'] = $fileName;
        }
        if (\Schema::hasColumn('support_messages', 'file_mime')) {
            $msgData['file_mime'] = $fileMime;
        }

        try {
            SupportMessage::create($msgData);
        } catch (Throwable $e) {
            \Log::error('[support_forum] не удалось сохранить ответ менеджера', [
                'chat_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Поднимаем чат если он был closed
        if ($chat->status === 'closed') {
            $chat->update(['status' => 'open']);
        }

        // === Отправляем клиенту ===
        $clientTgId = $chat->user->chat_id;
        try {
            if ($localPath && $isImage) {
                $this->telegram->sendPhoto([
                    'chat_id' => $clientTgId,
                    'photo' => InputFile::create(
                        \Illuminate\Support\Facades\Storage::disk('public')->path($localPath),
                        $fileName ?: 'photo.jpg'
                    ),
                    'caption' => $text !== '' ? $text : null,
                ]);
            } elseif ($localPath) {
                $this->telegram->sendDocument([
                    'chat_id' => $clientTgId,
                    'document' => InputFile::create(
                        \Illuminate\Support\Facades\Storage::disk('public')->path($localPath),
                        $fileName ?: 'file'
                    ),
                    'caption' => $text !== '' ? $text : null,
                ]);
            } else {
                $this->telegram->sendMessage([
                    'chat_id' => $clientTgId,
                    'text' => $text,
                ]);
            }
        } catch (Throwable $e) {
            \Log::error('[support_forum] не удалось переслать ответ менеджера клиенту', [
                'chat_id' => $chat->id,
                'client_tg_id' => $clientTgId,
                'error' => $e->getMessage(),
            ]);

            // Сообщаем менеджеру в ту же тему
            try {
                $this->telegram->sendMessage([
                    'chat_id' => config('services.support.group_id'),
                    'message_thread_id' => $topicId,
                    'text' => '⚠️ Не удалось доставить сообщение клиенту: ' . $e->getMessage(),
                ]);
            } catch (Throwable) {}
        }
    }

    /**
     * /close в теме — закрыть чат, уведомить клиента, закрыть тему.
     */
    private function handleTopicCloseCommand(SupportChat $chat): void
    {
        if ($chat->status === 'closed') {
            return;
        }

        $chat->update(['status' => 'closed']);
        $chat->user?->update(['step' => 'done', 'current_chat_id' => null]);

        $closedMsg = $this->t($chat->user, 'bot.chat_ended')
            ?: '🔚 Менеджер завершил чат. Спасибо за обращение!';
        try {
            // Одно сообщение — и текст закрытия, и клавиатура главного меню
            $this->sendMainMenu($chat->user->chat_id, $chat->user, $closedMsg);
        } catch (Throwable $e) {
            \Log::warning('[support_forum] не уведомили клиента о закрытии', [
                'chat_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->telegram->sendMessage([
                'chat_id' => config('services.support.group_id'),
                'message_thread_id' => (int) $chat->telegram_topic_id,
                'text' => '✅ Чат закрыт.',
            ]);
        } catch (Throwable) {}

        $this->forum->closeTopic($chat);
    }

    /**
     * Преобразовать публичный URL в относительный путь в storage/app/public.
     * Возвращает null, если URL не относится к этому storage.
     */
    private function urlToStoragePath(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $marker = '/storage/';
        $pos = strpos($url, $marker);
        if ($pos === false) {
            return null;
        }

        return substr($url, $pos + strlen($marker));
    }

    /**
     * Извлечь from-объект из апдейта (Update-message).
     */
    private function msgFrom($message): ?array
    {
        if (is_object($message) && method_exists($message, 'get')) {
            $from = $message->get('from');
            if (is_array($from)) return $from;
            if (is_object($from) && method_exists($from, 'all')) return $from->all();
        }

        $from = data_get($message, 'from');
        return is_array($from) ? $from : null;
    }
}
