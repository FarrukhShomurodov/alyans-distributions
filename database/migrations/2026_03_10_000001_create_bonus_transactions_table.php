<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_user_id')->constrained('bot_users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('type', [
                'accrual',
                'redemption',
                'manual_accrual',
                'manual_deduction',
                'refund',
            ]);
            $table->unsignedInteger('amount');
            $table->unsignedInteger('balance_after');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['bot_user_id', 'created_at']);
            $table->index('order_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_transactions');
    }
};
