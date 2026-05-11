<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->string('saved_last_name')->nullable();
            $table->string('saved_patronymic')->nullable();
            $table->string('saved_email')->nullable();
            $table->string('saved_delivery_address')->nullable();
            $table->string('saved_delivery_city')->nullable();
            $table->string('saved_delivery_method')->nullable();
            $table->string('saved_delivery_apartment')->nullable();
            $table->string('saved_delivery_floor')->nullable();
            $table->string('saved_delivery_entrance')->nullable();
            $table->string('saved_delivery_intercom')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bot_users', function (Blueprint $table) {
            $table->dropColumn([
                'saved_last_name', 'saved_patronymic', 'saved_email',
                'saved_delivery_address', 'saved_delivery_city', 'saved_delivery_method',
                'saved_delivery_apartment', 'saved_delivery_floor',
                'saved_delivery_entrance', 'saved_delivery_intercom',
            ]);
        });
    }
};
