<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_user_id')->nullable()->after('login');
            $table->string('telegram_username')->nullable()->after('telegram_user_id');
            $table->index('telegram_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropIndex(['telegram_user_id']);
            $table->dropColumn(['telegram_user_id', 'telegram_username']);
        });
    }
};
