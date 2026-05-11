<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('chat_templates', function (Blueprint $table) {
            $table->id();
            $table->string('command', 50)->unique()->comment('Команда без слэша, напр. "оплата"');
            $table->string('title', 150)->comment('Название шаблона для админки');
            $table->text('text')->comment('Текст ответа');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_templates');
    }
};
