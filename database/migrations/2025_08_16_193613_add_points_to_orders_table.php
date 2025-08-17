<?php

// File: database/migrations/2025_01_XX_XXXXXX_add_points_to_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Tambah kolom untuk poin yang digunakan
            $table->integer('points_used')->default(0)->after('voucher_discount');
            $table->decimal('points_discount', 10, 2)->default(0)->after('points_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['points_used', 'points_discount']);
        });
    }
};