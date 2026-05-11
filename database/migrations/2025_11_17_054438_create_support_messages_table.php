<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('support_chats')->cascadeOnDelete();

            // NULL = сообщение пользователя
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();

            $table->boolean('is_from_user')->default(true);
            $table->text('text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
