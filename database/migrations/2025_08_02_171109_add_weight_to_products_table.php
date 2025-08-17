<?php
// File: database/migrations/2025_08_02_061314_add_weight_to_products_table.php

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
        Schema::table('products', function (Blueprint $table) {
            // Check if weight column exists before adding
            if (!Schema::hasColumn('products', 'weight')) {
                $table->integer('weight')->default(300)->after('stock_quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Only drop if column exists
            if (Schema::hasColumn('products', 'weight')) {
                $table->dropColumn('weight');
            }
        });
    }
};