<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->foreignId('current_chat_id')
                ->nullable()
                ->after('step')
                ->constrained('support_chats')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_chat_id');
        });
    }
};
