<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Не оборачиваем в транзакцию — иначе любой провал внутри
     * (например, отсутствующий индекс) откатит всё подряд.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Индексы — через "IF EXISTS" (PG/SQLite/MySQL поддерживают)
        $this->dropIndexIfExists('orders_one_c_exported_at_index');
        $this->dropIndexIfExists('orders_sova_exported_at_index');

        // Колонки — через hasColumn (нет универсального DROP COLUMN IF EXISTS у Laravel)
        if (Schema::hasColumn('orders', 'one_c_exported_at')) {
            Schema::table('orders', function ($table) {
                $table->dropColumn('one_c_exported_at');
            });
        }
        if (Schema::hasColumn('orders', 'sova_exported_at')) {
            Schema::table('orders', function ($table) {
                $table->dropColumn('sova_exported_at');
            });
        }
    }

    public function down(): void
    {
        // Откат не нужен — внешние интеграции полностью удалены.
    }

    private function dropIndexIfExists(string $indexName): void
    {
        $driver = Schema::getConnection()->getDriverName();

        try {
            if (in_array($driver, ['pgsql', 'mysql', 'sqlite'], true)) {
                DB::statement("DROP INDEX IF EXISTS \"{$indexName}\"");
            }
        } catch (\Throwable $e) {
            // глушим — если индекса нет, для нас это нормально
        }
    }
};
