<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_settings', function (Blueprint $table) {
            $table->id();
            $table->string('active_type')->default('percent');
            $table->unsignedTinyInteger('discount_percent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_settings');
    }
};
