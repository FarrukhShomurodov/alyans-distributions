<?php

use App\Models\StockHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete();
            $table->enum('type', StockHistory::TYPE);
            $table->enum('source', StockHistory::SOURCE);
            $table->integer('quantity');
            $table->integer('previous_quantity');
            $table->integer('difference')->comment('Разница между старым и новым количеством');
            $table->foreignId('updated_by')->nullable()->constrained('admins')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('bot_users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
