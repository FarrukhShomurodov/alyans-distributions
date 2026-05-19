<?php

namespace App\Services;

use App\Models\BotUser;
use App\Models\SupportChat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

/**
 * Сервис интеграции поддержки с Telegram-супергруппой (forum).
 *
 * Каждому SupportChat соответствует своя тема в группе.
 * Менеджеры пишут в тему — бот пересылает клиенту.
 * Клиент пишет в бот — бот пересылает в тему.
 */
class SupportForumService
{
    private Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    /**
     * Включён ли механизм форума (есть ли группа в .env).
     */
    public function enabled(): bool
    {
        return (bool) config('services.support.group_id');
    }

    /**
     * ID супергруппы из конфига.
     */
    public function groupId(): ?string
    {
        return config('services.support.group_id') ?: null;
    }

    /**
     * Создать тему в группе под этот чат (если ещё не создана).
     * Возвращает ID темы или null если форум не настроен / создание упало.
     */
    public function ensureTopic(SupportChat $chat): ?int
    {
        if (!$this->enabled()) {
            return null;
        }

        if ($chat->telegram_topic_id) {
            return (int) $chat->telegram_topic_id;
        }

        $chat->loadMissing('user', 'order');
        $name = $this->buildTopicName($chat);

        try {
            $response = $this->raw('createForumTopic', [
                'chat_id' => $this->groupId(),
                'name'    => $name,
                'icon_color' => 0xFFD67E, // тёплый акцент
            ]);

            $topicId = (int) data_get($response, 'result.message_thread_id', 0);
            if (!$topicId) {
                Log::warning('[support_forum] createForumTopic вернул пусто', ['response' => $response]);
                return null;
            }

            $chat->update(['telegram_topic_id' => $topicId]);

            // Шапка темы — карточка клиента, чтобы менеджер сразу видел контекст
            $this->sendInfoCard($chat);

            return $topicId;
        } catch (\Throwable $e) {
            Log::error('[support_forum] не удалось создать тему', [
                'chat_id' => $chat->id,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Закрыть тему в группе.
     */
    public function closeTopic(SupportChat $chat): void
    {
        if (!$this->enabled() || !$chat->telegram_topic_id) {
            return;
        }

        try {
            $this->raw('closeForumTopic', [
                'chat_id'           => $this->groupId(),
                'message_thread_id' => (int) $chat->telegram_topic_id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[support_forum] closeForumTopic упал', [
                'chat_id' => $chat->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Снова открыть тему.
     */
    public function reopenTopic(SupportChat $chat): void
    {
        if (!$this->enabled() || !$chat->telegram_topic_id) {
            return;
        }

        try {
            $this->raw('reopenForumTopic', [
                'chat_id'           => $this->groupId(),
                'message_thread_id' => (int) $chat->telegram_topic_id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[support_forum] reopenForumTopic упал', [
                'chat_id' => $chat->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Переслать сообщение клиента в тему группы.
     *
     * @param SupportChat $chat
     * @param string $text
     * @param string|null $localPath  путь относительно storage/app/public
     * @param string|null $mime
     * @param bool $isImage
     */
    public function forwardClientMessage(
        SupportChat $chat,
        string $text,
        ?string $localPath = null,
        ?string $mime = null,
        bool $isImage = false,
        ?string $fileName = null
    ): void {
        if (!$this->enabled()) {
            return;
        }

        $topicId = $this->ensureTopic($chat);
        if (!$topicId) {
            return;
        }

        $prefix = $this->buildClientPrefix($chat);
        $caption = trim($prefix . ($text !== '' ? "\n" . $text : ''));

        try {
            if ($localPath && $isImage) {
                $this->telegram->sendPhoto([
                    'chat_id'           => $this->groupId(),
                    'message_thread_id' => $topicId,
                    'photo'             => InputFile::create(
                        Storage::disk('public')->path($localPath),
                        $fileName ?: 'photo.jpg'
                    ),
                    'caption'           => $caption,
                ]);
            } elseif ($localPath) {
                $this->telegram->sendDocument([
                    'chat_id'           => $this->groupId(),
                    'message_thread_id' => $topicId,
                    'document'          => InputFile::create(
                        Storage::disk('public')->path($localPath),
                        $fileName ?: 'file'
                    ),
                    'caption'           => $caption,
                ]);
            } else {
                $this->telegram->sendMessage([
                    'chat_id'           => $this->groupId(),
                    'message_thread_id' => $topicId,
                    'text'              => $caption,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[support_forum] не удалось переслать клиентское сообщение в тему', [
                'chat_id' => $chat->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отметить в теме что менеджер ответил из админки (sync для прозрачности).
     */
    public function postAdminMessageToTopic(
        SupportChat $chat,
        string $adminName,
        string $text,
        ?string $localPath = null,
        bool $isImage = false,
        ?string $fileName = null
    ): void {
        if (!$this->enabled()) {
            return;
        }

        $topicId = $this->ensureTopic($chat);
        if (!$topicId) {
            return;
        }

        $prefix = "🛠 <b>" . e($adminName) . "</b> · ответ из админки";
        $caption = trim($prefix . ($text !== '' ? "\n" . e($text) : ''));

        try {
            if ($localPath && $isImage) {
                $this->telegram->sendPhoto([
                    'chat_id'           => $this->groupId(),
                    'message_thread_id' => $topicId,
                    'photo'             => InputFile::create(
                        Storage::disk('public')->path($localPath),
                        $fileName ?: 'photo.jpg'
                    ),
                    'caption'           => $caption,
                    'parse_mode'        => 'HTML',
                ]);
            } elseif ($localPath) {
                $this->telegram->sendDocument([
                    'chat_id'           => $this->groupId(),
                    'message_thread_id' => $topicId,
                    'document'          => InputFile::create(
                        Storage::disk('public')->path($localPath),
                        $fileName ?: 'file'
                    ),
                    'caption'           => $caption,
                    'parse_mode'        => 'HTML',
                ]);
            } else {
                $this->telegram->sendMessage([
                    'chat_id'           => $this->groupId(),
                    'message_thread_id' => $topicId,
                    'text'              => $caption,
                    'parse_mode'        => 'HTML',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[support_forum] не удалось продублировать ответ админки в тему', [
                'chat_id' => $chat->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Карточка клиента — пинится в начале темы.
     */
    public function sendInfoCard(SupportChat $chat): void
    {
        if (!$this->enabled() || !$chat->telegram_topic_id) {
            return;
        }

        $chat->loadMissing('user', 'order');
        $user = $chat->user;

        if (!$user) {
            return;
        }

        $ordersCount = \App\Models\Order::where('user_id', $user->id)->count();
        $totalSum = (int) \App\Models\Order::where('user_id', $user->id)->sum('total');

        $lines = [];
        $lines[] = "👤 <b>Клиент</b>";
        $fio = trim(($user->first_name ?? '') . ' ' . ($user->second_name ?? ''));
        if ($fio !== '') {
            $lines[] = "• ФИО: " . e($fio);
        }
        if ($user->uname) {
            $lines[] = "• Telegram: @" . e($user->uname);
        }
        if ($user->phone) {
            $lines[] = "• Телефон: " . e($user->phone);
        }
        $lines[] = "• Chat ID: <code>" . e($user->chat_id) . "</code>";

        if ($chat->order_id) {
            $lines[] = "";
            $lines[] = "📦 <b>Заказ №{$chat->order_id}</b>";
        }

        if ($ordersCount > 0) {
            $lines[] = "";
            $lines[] = "🛒 Заказов: <b>{$ordersCount}</b>, на сумму <b>" . number_format($totalSum, 0, '.', ' ') . " ₽</b>";
        }

        $lines[] = "";
        $lines[] = "💬 Просто отвечайте в этой теме — клиент получит ответ.";
        $lines[] = "🔚 Команды: /close — закрыть, /info — обновить карточку.";

        try {
            $msg = $this->telegram->sendMessage([
                'chat_id'           => $this->groupId(),
                'message_thread_id' => (int) $chat->telegram_topic_id,
                'text'              => implode("\n", $lines),
                'parse_mode'        => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            // Закрепить шапку
            try {
                $this->raw('pinChatMessage', [
                    'chat_id'    => $this->groupId(),
                    'message_id' => (int) data_get($msg, 'message_id'),
                    'disable_notification' => true,
                ]);
            } catch (\Throwable $e) {
                // pin не критичен
            }
        } catch (\Throwable $e) {
            Log::warning('[support_forum] не удалось отправить карточку', [
                'chat_id' => $chat->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Сообщение «клиент завершил чат».
     */
    public function notifyClientClosed(SupportChat $chat): void
    {
        if (!$this->enabled() || !$chat->telegram_topic_id) {
            return;
        }

        try {
            $this->telegram->sendMessage([
                'chat_id'           => $this->groupId(),
                'message_thread_id' => (int) $chat->telegram_topic_id,
                'text'              => "🔚 Клиент завершил чат.",
            ]);
        } catch (\Throwable $e) {
            // не критично
        }
    }

    private function buildTopicName(SupportChat $chat): string
    {
        $user = $chat->user;
        $bits = [];

        if ($chat->order_id) {
            $bits[] = "📦 #{$chat->order_id}";
        } else {
            $bits[] = "💬";
        }

        $fio = trim(($user?->first_name ?? '') . ' ' . ($user?->second_name ?? ''));
        if ($fio === '' && $user?->uname) {
            $fio = '@' . $user->uname;
        }
        if ($fio === '') {
            $fio = 'Клиент #' . ($user->id ?? '?');
        }
        $bits[] = mb_substr($fio, 0, 30);

        $name = implode(' · ', $bits);
        return mb_substr($name, 0, 128); // лимит Telegram
    }

    private function buildClientPrefix(SupportChat $chat): string
    {
        $user = $chat->user;
        if (!$user) {
            return '💬';
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->second_name ?? ''));
        if ($name === '' && $user->uname) {
            $name = '@' . $user->uname;
        }
        if ($name === '') {
            $name = 'Клиент';
        }

        return "💬 {$name}:";
    }

    /**
     * Сырой вызов Bot API (для тех методов, что не покрыты библиотекой).
     */
    private function raw(string $method, array $params): array
    {
        $url = sprintf('https://api.telegram.org/bot%s/%s', env('TELEGRAM_BOT_TOKEN'), $method);
        $response = Http::asJson()->post($url, $params);

        if (!$response->successful()) {
            throw new \RuntimeException("Telegram API {$method} failed: " . $response->body());
        }

        return $response->json() ?? [];
    }
}
