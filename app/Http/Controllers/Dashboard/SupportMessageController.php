<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Services\SupportForumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Keyboard\Keyboard;

class SupportMessageController
{
    public function send(Request $request, SupportChat $chat): RedirectResponse
    {
        $request->validate([
            'text' => 'nullable|string',
            'attachment' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,zip,rar',
        ]);

        if (! $request->filled('text') && ! $request->hasFile('attachment')) {
            return back()->with('error', 'Нужно ввести текст или прикрепить файл.');
        }

        $adminId = auth()->user()->id;
        $text = trim((string) $request->input('text', ''));
        $localUrl = null;
        $fileName = null;
        $fileMime = null;
        $isImage = false;
        $localPath = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileMime = $file->getMimeType();
            $fileName = $file->getClientOriginalName();
            $isImage = str_starts_with((string) $fileMime, 'image/');
            // Сохраняем во storage/app/public/support_files
            $localPath = $file->store('support_files', 'public');
            $localUrl = asset('storage/' . $localPath);
        }

        // Сохраняем в БД
        $msgData = [
            'chat_id' => $chat->id,
            'admin_id' => $adminId,
            'is_from_user' => false,
            'text' => $text !== '' ? $text : ($isImage ? '📷 Фото' : ($fileName ? '📎 ' . $fileName : '')),
            'photo_url' => $localUrl,
            'source' => 'support',
            'source_order_id' => $chat->order_id,
        ];
        if (Schema::hasColumn('support_messages', 'file_name') && !$isImage) {
            $msgData['file_name'] = $fileName;
        }
        if (Schema::hasColumn('support_messages', 'file_mime') && !$isImage) {
            $msgData['file_mime'] = $fileMime;
        }

        SupportMessage::query()->create($msgData);

        // Отправляем в Telegram пользователю
        try {
            $tg = new Api(env('TELEGRAM_BOT_TOKEN'));
            $tgChatId = $chat->user->chat_id;

            if ($localPath && $isImage) {
                $tg->sendPhoto([
                    'chat_id' => $tgChatId,
                    'photo' => InputFile::create(Storage::disk('public')->path($localPath), $fileName ?: 'photo.jpg'),
                    'caption' => $text !== '' ? $text : null,
                ]);
            } elseif ($localPath) {
                $tg->sendDocument([
                    'chat_id' => $tgChatId,
                    'document' => InputFile::create(Storage::disk('public')->path($localPath), $fileName ?: 'file'),
                    'caption' => $text !== '' ? $text : null,
                ]);
            } else {
                $tg->sendMessage([
                    'chat_id' => $tgChatId,
                    'text' => $text,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[support_send] не удалось отправить в Telegram', [
                'chat_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Сообщение сохранено, но не отправилось в Telegram: ' . $e->getMessage());
        }

        // === Дублируем в менеджерскую тему (если форум настроен) ===
        try {
            $forum = new SupportForumService();
            if ($forum->enabled()) {
                $adminName = auth()->user()?->login ?? 'Админ';
                $forum->postAdminMessageToTopic(
                    $chat->loadMissing('user', 'order'),
                    $adminName,
                    $text,
                    $localPath,
                    $isImage,
                    $fileName
                );
            }
        } catch (\Throwable $e) {
            // не критично, ответ клиенту уже ушёл
            Log::warning('[support_send] не удалось продублировать ответ в тему', [
                'chat_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Сообщение отправлено клиенту.');
    }

    public function close(SupportChat $chat): RedirectResponse
    {
        $chat->update(['status' => 'closed']);

        $botUser = $chat->user;
        $botUser->update(['step' => 'done', 'current_chat_id' => null]);

        $this->sendMainMenu($chat->user->chat_id, $botUser, $this->t($botUser, 'bot.chat_ended'));

        // Закрыть тему в Telegram-группе
        try {
            $forum = new SupportForumService();
            if ($forum->enabled() && $chat->telegram_topic_id) {
                $forum->postAdminMessageToTopic(
                    $chat->loadMissing('user', 'order'),
                    auth()->user()?->login ?? 'Админ',
                    '✅ Чат закрыт из админки.',
                );
                $forum->closeTopic($chat);
            }
        } catch (\Throwable $e) {
            Log::warning('[support_close] не удалось закрыть тему', [
                'chat_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('support.index');
    }

    private function sendMainMenu($chatId, $user, ?string $customText = null)
    {
        $menu = Keyboard::make([
            'keyboard' => [
                [['text' => $this->t($user, 'bot.menu.orders')]],
                [['text' => $this->t($user, 'bot.menu.profile')]],
                [['text' => $this->t($user, 'bot.menu.manager')]],
                [['text' => $this->t($user, 'bot.menu.shop')]],
            ],
            'resize_keyboard' => true,
        ]);

        $tg = new Api(env('TELEGRAM_BOT_TOKEN'));
        $tg->sendMessage([
            'chat_id' => $chatId,
            'text' => $customText ?? $this->t($user, 'bot.thanks', ['name' => $user->first_name]),
            'reply_markup' => $menu,
        ]);
    }

    private function t($user, $key, $replace = [])
    {
        app()->setLocale($user->lang ?? 'ru');

        return __($key, $replace);
    }
}
