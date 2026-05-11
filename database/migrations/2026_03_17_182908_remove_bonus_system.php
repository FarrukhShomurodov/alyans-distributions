<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Удаляем таблицу бонусных транзакций
        Schema::dropIfExists('bonus_transactions');

        // Удаляем колонку bonus_balance из bot_users
        if (Schema::hasColumn('bot_users', 'bonus_balance')) {
            Schema::table('bot_users', function (Blueprint $table) {
                $table->dropColumn('bonus_balance');
            });
        }

        // Удаляем колонки bonus_used и bonus_earned из orders
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'bonus_used')) {
                $table->dropColumn('bonus_used');
            }
            if (Schema::hasColumn('orders', 'bonus_earned')) {
                $table->dropColumn('bonus_earned');
            }
        });
    }

    public function down(): void
    {
        // Восстановление при откате
        Schema::create('bonus_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['accrual', 'redemption', 'manual_accrual', 'manual_deduction', 'refund']);
            $table->unsignedInteger('amount');
            $table->unsignedInteger('balance_after');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['bot_user_id', 'created_at']);
            $table->index(['order_id']);
            $table->index(['type']);
        });

        if (!Schema::hasColumn('bot_users', 'bonus_balance')) {
            Schema::table('bot_users', function (Blueprint $table) {
                $table->unsignedInteger('bonus_balance')->default(0);
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'bonus_used')) {
                $table->unsignedInteger('bonus_used')->default(0);
            }
            if (!Schema::hasColumn('orders', 'bonus_earned')) {
                $table->unsignedInteger('bonus_earned')->default(0);
            }
        });
    }
};
