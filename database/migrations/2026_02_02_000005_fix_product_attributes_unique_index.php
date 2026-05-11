<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up(): void
    {
        // Удаляем старый индекс если существует
        DB::statement('DROP INDEX IF EXISTS product_attributes_product_id_attribute_id_unique');

        // Создаём новый только если его нет
        DB::statement('
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes
                    WHERE indexname = \'product_attributes_product_id_attribute_id_value_unique\'
                ) THEN
                    CREATE UNIQUE INDEX product_attributes_product_id_attribute_id_value_unique
                    ON product_attributes (product_id, attribute_id, value);
                END IF;
            END
            $$;
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS product_attributes_product_id_attribute_id_value_unique');

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS product_attributes_product_id_attribute_id_unique
            ON product_attributes (product_id, attribute_id);
        ');
    }
};
