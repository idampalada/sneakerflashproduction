<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // LANGKAH 1: Tambahkan kolom status terlebih dahulu
        Schema::table('products', function (Blueprint $table) {
            // Cek apakah kolom status sudah ada
            if (!Schema::hasColumn('products', 'status')) {
                // Tambahkan kolom status setelah kolom is_active dengan tipe VARCHAR
                $table->string('status', 50)->default('active')->after('is_active');
                
                // Buat B-tree index untuk kolom status (standar PostgreSQL)
                $table->index('status', 'products_status_idx');
            }
        });
        
        // LANGKAH 2: Baru kemudian update data
        // Perintah ini harus dilakukan SETELAH kolom status dibuat
        DB::statement("
            UPDATE products 
            SET status = CASE 
                WHEN is_active = true THEN 'active'::varchar 
                ELSE 'inactive'::varchar 
            END
        ");
        
        // LANGKAH 3: Tambahkan komentar pada kolom
        DB::statement("COMMENT ON COLUMN products.status IS 'Status produk: active, inactive, draft, etc.'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Hapus index dengan nama spesifik (PostgreSQL-friendly)
            if (Schema::hasColumn('products', 'status')) {
                // Hapus index hanya jika kolom status ada
                DB::statement('DROP INDEX IF EXISTS products_status_idx');
                
                // Hapus kolom status
                $table->dropColumn('status');
            }
        });
    }
};