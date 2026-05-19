<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_topic_id')->nullable()->after('status');
            $table->index('telegram_topic_id');
        });
    }

    public function down(): void
    {
        Schema::table('support_chats', function (Blueprint $table) {
            $table->dropIndex(['telegram_topic_id']);
            $table->dropColumn('telegram_topic_id');
        });
    }
};
