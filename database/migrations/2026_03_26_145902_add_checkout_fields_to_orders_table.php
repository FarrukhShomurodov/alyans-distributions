<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('delivery_phone');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('patronymic')->nullable()->after('last_name');
            $table->string('email')->nullable()->after('patronymic');
            $table->string('phone')->nullable()->after('email');
            $table->text('comment')->nullable()->after('phone');
            $table->string('delivery_method')->nullable()->after('comment');
            $table->string('delivery_pvz_code')->nullable()->after('delivery_method');
            $table->string('delivery_pvz_name')->nullable()->after('delivery_pvz_code');
            $table->decimal('delivery_price', 10, 2)->default(0)->after('delivery_pvz_name');
            $table->string('delivery_city')->nullable()->after('delivery_price');
            $table->string('delivery_apartment')->nullable()->after('delivery_city');
            $table->string('delivery_floor')->nullable()->after('delivery_apartment');
            $table->string('delivery_entrance')->nullable()->after('delivery_floor');
            $table->string('delivery_intercom')->nullable()->after('delivery_entrance');
            $table->date('delivery_date')->nullable()->after('delivery_intercom');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'patronymic', 'email', 'phone', 'comment',
                'delivery_method', 'delivery_pvz_code', 'delivery_pvz_name', 'delivery_price',
                'delivery_city', 'delivery_apartment', 'delivery_floor', 'delivery_entrance',
                'delivery_intercom', 'delivery_date',
            ]);
        });
    }
};
