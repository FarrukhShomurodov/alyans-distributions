<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        // Снимаем старый check-constraint на payment_type и ставим новый, разрешающий 'sbp'
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_type_check");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_type_check CHECK (payment_type IN ('payme', 'cash', 'sbp'))");
    }

    public function down(): void
    {
        // Возвращаем старое ограничение
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_type_check");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_type_check CHECK (payment_type IN ('payme', 'cash'))");
    }
};
