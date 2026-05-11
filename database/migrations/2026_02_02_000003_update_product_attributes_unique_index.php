<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'attribute_id']);
            $table->unique(['product_id', 'attribute_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'attribute_id', 'value']);
            $table->unique(['product_id', 'attribute_id']);
        });
    }
};