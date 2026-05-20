<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'one_c_exported_at')) {
                try { $table->dropIndex(['one_c_exported_at']); } catch (\Throwable $e) {}
                $table->dropColumn('one_c_exported_at');
            }
            if (Schema::hasColumn('orders', 'sova_exported_at')) {
                try { $table->dropIndex(['sova_exported_at']); } catch (\Throwable $e) {}
                $table->dropColumn('sova_exported_at');
            }
        });
    }

    public function down(): void
    {
        // Откат не нужен — внешние интеграции полностью удалены из проекта.
    }
};
