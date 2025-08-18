<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ginee_product_mappings')) {
            Schema::create('ginee_product_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('ginee_master_sku')->index();
                $table->string('ginee_product_id')->nullable()->index();
                $table->string('ginee_warehouse_id')->nullable();
                
                // Mapping configuration
                $table->boolean('sync_enabled')->default(true);
                $table->boolean('stock_sync_enabled')->default(true);
                $table->boolean('price_sync_enabled')->default(true);
                
                // Last sync data
                $table->timestamp('last_product_sync')->nullable();
                $table->timestamp('last_stock_sync')->nullable();
                $table->timestamp('last_price_sync')->nullable();
                
                // Sync results
                $table->integer('stock_quantity_ginee')->nullable();
                $table->decimal('price_ginee', 15, 2)->nullable();
                $table->jsonb('ginee_product_data')->nullable();
                
                $table->timestamps();

                // Constraints and indexes
                $table->unique(['product_id', 'ginee_master_sku'], 'ginee_product_mappings_unique');
                $table->index('ginee_master_sku', 'ginee_product_mappings_master_sku_idx');
                $table->index('sync_enabled', 'ginee_product_mappings_sync_enabled_idx');
                $table->index(['sync_enabled', 'stock_sync_enabled'], 'ginee_product_mappings_sync_composite_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ginee_product_mappings');
    }
};