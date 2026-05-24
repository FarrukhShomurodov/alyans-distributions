<?php

namespace App\Console\Commands;

use App\Imports\ProductsImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductsCommand extends Command
{
    protected $signature = 'products:import {path : путь к XLSX/CSV (например storage/app/imports/ТМЦ.xlsx)}';

    protected $description = 'Импортировать товары из Excel/CSV (например, выгрузка из 1С)';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            // Попробовать относительно base_path
            $candidate = base_path($path);
            if (file_exists($candidate)) {
                $path = $candidate;
            } else {
                $this->error("Файл не найден: {$path}");
                return self::FAILURE;
            }
        }

        $this->info("Импорт из: {$path}");

        $import = new ProductsImport();
        try {
            Excel::import($import, $path);
        } catch (\Throwable $e) {
            $this->error('Ошибка импорта: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Создано:  {$import->getImportedCount()}");
        $this->info("Обновлено: {$import->getUpdatedCount()}");

        $skipped = $import->getSkippedRows();
        if (!empty($skipped)) {
            $this->warn("Пропущено строк: " . count($skipped));
            foreach (array_slice($skipped, 0, 20) as $name) {
                $this->line("  · {$name}");
            }
            if (count($skipped) > 20) {
                $this->line("  ... и ещё " . (count($skipped) - 20));
            }
        }

        return self::SUCCESS;
    }
}
