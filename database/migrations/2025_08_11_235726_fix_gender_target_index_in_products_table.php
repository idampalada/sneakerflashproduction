<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop index yang salah dari migration sebelumnya
        DB::statement('DROP INDEX IF EXISTS products_product_type_gender_target_index');
        
        // 2. Convert JSON to JSONB terlebih dahulu untuk PostgreSQL
        DB::statement('ALTER TABLE products ALTER COLUMN gender_target TYPE jsonb USING gender_target::jsonb');
        
        // 3. Buat GIN index yang benar untuk JSONB operations
        DB::statement("
            CREATE INDEX IF NOT EXISTS products_gender_target_gin_index
            ON products USING GIN (gender_target jsonb_ops)
            WHERE gender_target IS NOT NULL
        ");
        
        // 4. Buat B-tree index untuk product_type saja (karena mixed type index sulit)
        DB::statement("
            CREATE INDEX IF NOT EXISTS products_product_type_active_index
            ON products (product_type, is_active)
            WHERE product_type IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_gender_target_gin_index');
        DB::statement('DROP INDEX IF EXISTS products_product_type_active_index');
        
        // Revert back to JSON if needed
        DB::statement('ALTER TABLE products ALTER COLUMN gender_target TYPE json USING gender_target::json');
    }
};