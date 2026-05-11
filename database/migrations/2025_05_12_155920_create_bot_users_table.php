<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_users', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('chat_id')->unique()->comment('Telegram chat ID');
            $table->string('first_name')->nullable()->comment('Имя пользователя');
            $table->string('second_name')->nullable()->comment('Фамилия пользователя');
            $table->string('uname')->nullable()->comment('Юзернейм Telegram (@username)');
            $table->string('phone')->nullable()->comment('Номер телефона');

            $table->string('step')->nullable()->comment('Текущ  ий шаг в сценарии бота');
            $table->boolean('is_active')->default(true)->comment('Активен ли пользователь');
            $table->string('lang')->nullable()->default('ru');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_users');
    }
};
