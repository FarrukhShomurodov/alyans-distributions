<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand')->nullable()->after('name');
                $table->index('brand');
            }
            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit', 32)->nullable()->default('шт')->after('price');
            }
            if (!Schema::hasColumn('products', 'is_top')) {
                $table->boolean('is_top')->default(false)->after('is_active');
                $table->index('is_top');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'brand')) {
                try { $table->dropIndex(['brand']); } catch (\Throwable $e) {}
                $table->dropColumn('brand');
            }
            if (Schema::hasColumn('products', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('products', 'is_top')) {
                try { $table->dropIndex(['is_top']); } catch (\Throwable $e) {}
                $table->dropColumn('is_top');
            }
        });
    }
};
