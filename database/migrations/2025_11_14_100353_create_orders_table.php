<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('bot_users')->cascadeOnDelete();
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('status', ['new', 'in_process', 'done', 'canceled'])->default('new');
            $table->enum('payment_type', ['payme', 'cash'])->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
