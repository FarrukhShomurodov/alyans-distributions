<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('photo_url');
            $table->string('file_mime', 100)->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'file_mime']);
        });
    }
};
