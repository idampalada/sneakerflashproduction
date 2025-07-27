<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Add product_snapshot column if it doesn't exist
            if (!Schema::hasColumn('order_items', 'product_snapshot')) {
                $table->json('product_snapshot')->nullable()->after('total_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'product_snapshot')) {
                $table->dropColumn('product_snapshot');
            }
        });
    }
};