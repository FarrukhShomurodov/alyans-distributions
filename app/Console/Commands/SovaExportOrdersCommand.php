<?php

namespace App\Console\Commands;

use App\Services\SovaIntegrationService;
use Illuminate\Console\Command;

class SovaExportOrdersCommand extends Command
{
    protected $signature = 'sova:export-orders {--all : Отправить все заказы, включая уже отправленные}';

    protected $description = 'Отправка новых заказов в Сова API';

    public function handle(SovaIntegrationService $service): int
    {
        $onlyNew = !$this->option('all');

        $this->info($onlyNew ? 'Отправляю новые заказы в Сову...' : 'Отправляю ВСЕ заказы в Сову...');

        $result = $service->exportOrders($onlyNew);

        if (isset($result['error'])) {
            $this->error($result['error']);
            return self::FAILURE;
        }

        $this->info("Отправлено заказов: {$result['exported']}");

        if (!empty($result['order_ids'])) {
            $this->line('  ID заказов: ' . implode(', ', $result['order_ids']));
        }

        if (isset($result['message'])) {
            $this->info($result['message']);
        }

        return self::SUCCESS;
    }
}
