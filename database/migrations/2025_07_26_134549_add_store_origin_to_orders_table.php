<?php
// File: database/migrations/2025_07_25_150500_add_store_origin_to_orders_table.php
// Jalankan: php artisan make:migration add_store_origin_to_orders_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add store origin information
            $table->json('store_origin')->nullable()->after('billing_address');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('store_origin');
        });
    }
};

/*
Atau jika ingin update migration yang sudah ada, tambahkan di orders table:

Schema::create('orders', function (Blueprint $table) {
    // ... existing fields ...
    
    // Addresses (JSON)
    $table->json('shipping_address');
    $table->json('billing_address');
    
    // ✅ ADD: Store origin info
    $table->json('store_origin')->nullable()->comment('Origin store location info');
    
    // ... rest of fields ...
});

Store origin akan berisi:
{
    "address": "Jl. Bank Exim No.37, RT.6/RW.1, Pd. Pinang, Kec. Kby. Lama",
    "city": "Jakarta Selatan", 
    "province": "DKI Jakarta",
    "postal_code": "12310",
    "city_id": "158"
}
*/