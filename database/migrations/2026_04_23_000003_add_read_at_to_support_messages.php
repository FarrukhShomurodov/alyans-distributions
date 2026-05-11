<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('text');
            $table->index(['chat_id', 'is_from_user', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropIndex(['chat_id', 'is_from_user', 'read_at']);
            $table->dropColumn('read_at');
        });
    }
};
