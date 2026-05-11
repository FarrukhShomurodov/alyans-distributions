<?php

namespace App\Console\Commands;

use App\Services\SovaIntegrationService;
use Illuminate\Console\Command;

class SovaSyncCommand extends Command
{
    protected $signature = 'sova:sync {type=all : Тип синхронизации: all, categories, products, stocks}';

    protected $description = 'Синхронизация данных из Сова API (категории, товары, остатки)';

    public function handle(SovaIntegrationService $service): int
    {
        $type = $this->argument('type');

        $this->info("Начинаю синхронизацию: {$type}...");

        $result = match ($type) {
            'categories' => ['categories' => $service->syncCategories()],
            'products' => ['products' => $service->syncProducts()],
            'stocks' => ['stocks' => $service->syncStocks()],
            'all' => $service->syncAll(),
            default => null,
        };

        if ($result === null) {
            $this->error("Неизвестный тип: {$type}. Используйте: all, categories, products, stocks");
            return self::FAILURE;
        }

        foreach ($result as $section => $data) {
            $this->newLine();
            $this->info("=== {$section} ===");

            if (isset($data['error'])) {
                $this->error($data['error']);
                continue;
            }

            if (isset($data['created'])) {
                $this->line("  Создано: {$data['created']}");
            }
            if (isset($data['updated'])) {
                $this->line("  Обновлено: {$data['updated']}");
            }
            if (isset($data['skipped'])) {
                $this->line("  Пропущено: {$data['skipped']}");
            }
            if (!empty($data['errors'])) {
                $this->warn("  Ошибок: " . count($data['errors']));
                foreach (array_slice($data['errors'], 0, 10) as $err) {
                    $this->line("    - [{$err['index']}] {$err['reason']}");
                }
            }
        }

        $this->newLine();
        $this->info('Синхронизация завершена.');

        return self::SUCCESS;
    }
}
