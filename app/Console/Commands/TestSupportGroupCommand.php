<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestSupportGroupCommand extends Command
{
    protected $signature = 'support:test-group';

    protected $description = 'Проверить подключение бота к группе поддержки (getChat + ME)';

    public function handle(): int
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $groupId = config('services.support.group_id');

        $this->line('');
        $this->info('═══ Диагностика менеджерской группы ═══');
        $this->line('');

        if (!$token) {
            $this->error('✗ TELEGRAM_BOT_TOKEN не задан в .env');
            return self::FAILURE;
        }
        $this->line('✓ TELEGRAM_BOT_TOKEN: ' . substr($token, 0, 10) . '...');

        if (!$groupId) {
            $this->error('✗ TELEGRAM_SUPPORT_GROUP_ID не задан в .env');
            $this->line('');
            $this->warn('Как получить ID:');
            $this->line('  1. Создай супергруппу с включёнными темами (Forum)');
            $this->line('  2. Добавь бота админом с правом "Управлять темами"');
            $this->line('  3. Напиши что-нибудь в эту группу');
            $this->line('  4. Открой storage/logs/laravel.log — там увидишь "TELEGRAM_SUPPORT_GROUP_ID=..." с правильным значением');
            return self::FAILURE;
        }
        $this->line("✓ TELEGRAM_SUPPORT_GROUP_ID: {$groupId}");
        $this->line('');

        // getMe
        $me = Http::get("https://api.telegram.org/bot{$token}/getMe")->json();
        if (!data_get($me, 'ok')) {
            $this->error('✗ getMe упал: ' . json_encode($me, JSON_UNESCAPED_UNICODE));
            return self::FAILURE;
        }
        $username = data_get($me, 'result.username');
        $this->line("✓ Бот: @{$username} (id=" . data_get($me, 'result.id') . ')');
        $this->line('');

        // getChat
        $chat = Http::get("https://api.telegram.org/bot{$token}/getChat", ['chat_id' => $groupId])->json();
        if (!data_get($chat, 'ok')) {
            $this->error('✗ getChat вернул: ' . json_encode($chat, JSON_UNESCAPED_UNICODE));
            $this->line('');
            $this->warn('Что проверить:');
            $this->line('  • Бот добавлен в группу с этим ID? (любой администратор группы → +@'.$username.')');
            $this->line('  • ID корректный? Для супергрупп должен начинаться на -100 (например -1001234567890)');
            $this->line('  • Если в группе ещё не было ни одного сообщения от бота, попробуй просто написать /start@'.$username.' в группе и потом повторить эту команду');
            return self::FAILURE;
        }

        $result = $chat['result'] ?? [];
        $this->line('✓ Группа найдена:');
        $this->line('  • title: ' . ($result['title'] ?? '—'));
        $this->line('  • type: ' . ($result['type'] ?? '—'));
        $this->line('  • is_forum: ' . (($result['is_forum'] ?? false) ? 'да' : 'НЕТ ❌'));
        $this->line('');

        if (empty($result['is_forum'])) {
            $this->warn('⚠️ У группы НЕ включены темы (Forum). Включи в настройках группы → "Темы".');
            return self::FAILURE;
        }

        // getChatMember для самого бота — проверить права
        $botId = data_get($me, 'result.id');
        $member = Http::get("https://api.telegram.org/bot{$token}/getChatMember", [
            'chat_id' => $groupId,
            'user_id' => $botId,
        ])->json();

        if (!data_get($member, 'ok')) {
            $this->error('✗ Не удалось проверить права бота: ' . json_encode($member, JSON_UNESCAPED_UNICODE));
            return self::FAILURE;
        }

        $status = data_get($member, 'result.status');
        $canManageTopics = (bool) data_get($member, 'result.can_manage_topics', false);

        $this->line("✓ Статус бота в группе: {$status}");
        $this->line('  • can_manage_topics: ' . ($canManageTopics ? '✅ да' : '❌ НЕТ — сделай бота админом с правом «Управлять темами»'));
        $this->line('');

        if ($status !== 'administrator' || !$canManageTopics) {
            $this->warn('⚠️ Боту нужны права администратора с правом «Управлять темами» (can_manage_topics).');
            return self::FAILURE;
        }

        // Пробуем создать тестовую тему
        $this->line('Пробую создать тестовую тему…');
        $create = Http::asJson()->post("https://api.telegram.org/bot{$token}/createForumTopic", [
            'chat_id' => $groupId,
            'name' => 'TEST · диагностика (можно удалить)',
        ])->json();

        if (!data_get($create, 'ok')) {
            $this->error('✗ createForumTopic упал: ' . json_encode($create, JSON_UNESCAPED_UNICODE));
            return self::FAILURE;
        }

        $topicId = data_get($create, 'result.message_thread_id');
        $this->info("✅ Тестовая тема создана (message_thread_id={$topicId}). Можешь её удалить.");
        $this->line('');
        $this->info('═══ Всё ок, интеграция готова к работе ═══');

        return self::SUCCESS;
    }
}
