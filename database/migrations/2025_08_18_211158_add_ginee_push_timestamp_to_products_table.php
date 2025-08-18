<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Tambah field push timestamp (field sync sudah ada)
            $table->timestamp('ginee_last_stock_push')->nullable()->after('ginee_last_stock_sync')->comment('Terakhir push stock ke Ginee');
            
            // Index untuk performance
            $table->index('ginee_last_stock_push', 'idx_products_ginee_push');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_ginee_push');
            $table->dropColumn('ginee_last_stock_push');
        });
    }
};