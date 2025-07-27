<?php
// File: database/migrations/add_product_price_to_order_items_safely.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cek apakah ada data di tabel
        $hasData = DB::table('order_items')->exists();
        
        if ($hasData) {
            // Jika ada data, tambah kolom sebagai nullable dulu
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'product_price')) {
                    $table->decimal('product_price', 12, 2)->nullable()->after('product_sku');
                }
            });
            
            // Update existing records: copy unit_price to product_price
            DB::table('order_items')
                ->whereNull('product_price')
                ->update(['product_price' => DB::raw('unit_price')]);
                
            // Sekarang buat NOT NULL
            DB::statement('ALTER TABLE order_items ALTER COLUMN product_price SET NOT NULL');
        } else {
            // Jika tidak ada data, langsung buat NOT NULL
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'product_price')) {
                    $table->decimal('product_price', 12, 2)->after('product_sku');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'product_price')) {
                $table->dropColumn('product_price');
            }
        });
    }
};