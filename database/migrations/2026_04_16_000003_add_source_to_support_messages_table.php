<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            // order | support | bot
            $table->string('source', 20)->nullable()->after('text');
            $table->unsignedBigInteger('source_order_id')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_order_id']);
        });
    }
};
