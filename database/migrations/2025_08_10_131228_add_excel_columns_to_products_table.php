<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // SKU Information
            if (!Schema::hasColumn('products', 'sku_parent')) {
                $table->string('sku_parent')->nullable()->index('products_sku_parent_index'); // explicit name
            }

            // Dimensions
            if (!Schema::hasColumn('products', 'length')) {
                $table->decimal('length', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'width')) {
                $table->decimal('width', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'height')) {
                $table->decimal('height', 8, 2)->nullable();
            }

            // Product Classification
            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type')->nullable()->index('products_product_type_index');
            }

            // Sale Management
            if (!Schema::hasColumn('products', 'is_featured_sale')) {
                $table->boolean('is_featured_sale')->default(false)->index('products_is_featured_sale_index');
            }
            if (!Schema::hasColumn('products', 'sale_start_date')) {
                $table->date('sale_start_date')->nullable();
            }
            if (!Schema::hasColumn('products', 'sale_end_date')) {
                $table->date('sale_end_date')->nullable();
            }

            // JSON columns
            if (!Schema::hasColumn('products', 'available_sizes')) {
                $table->json('available_sizes')->nullable();
            }
            if (!Schema::hasColumn('products', 'available_colors')) {
                $table->json('available_colors')->nullable();
            }

            // Safe B-Tree indexes (kolom non-JSON)
            $table->index(['is_active', 'stock_quantity'], 'products_is_active_stock_quantity_index');
            $table->index(['brand', 'is_active'], 'products_brand_is_active_index');
            $table->index(['sale_price', 'is_featured_sale'], 'products_sale_price_is_featured_sale_index');

            // JANGAN buat index langsung di kolom JSON:
            // $table->index(['product_type', 'gender_target']);
        });

        // ✅ Expression index untuk JSON key (PostgreSQL)
        // Sesuaikan 'target' dengan key yang kamu gunakan di JSON gender_target
        DB::statement("
            CREATE INDEX products_product_type_gender_target_index
            ON products (
                (product_type),
                (gender_target->>'target')
            )
        ");
    }

    public function down(): void
    {
        // Drop expression index dulu
        DB::statement('DROP INDEX IF EXISTS products_product_type_gender_target_index');

        Schema::table('products', function (Blueprint $table) {
            // Drop named indexes
            $table->dropIndex('products_sku_parent_index');
            $table->dropIndex('products_is_active_stock_quantity_index');
            $table->dropIndex('products_brand_is_active_index');
            $table->dropIndex('products_sale_price_is_featured_sale_index');
            $table->dropIndex('products_product_type_index');
            $table->dropIndex('products_is_featured_sale_index');

            // Drop columns
            if (Schema::hasColumn('products', 'sku_parent')) {
                $table->dropColumn('sku_parent');
            }
            if (Schema::hasColumn('products', 'length')) {
                $table->dropColumn('length');
            }
            if (Schema::hasColumn('products', 'width')) {
                $table->dropColumn('width');
            }
            if (Schema::hasColumn('products', 'height')) {
                $table->dropColumn('height');
            }
            if (Schema::hasColumn('products', 'product_type')) {
                $table->dropColumn('product_type');
            }
            if (Schema::hasColumn('products', 'is_featured_sale')) {
                $table->dropColumn('is_featured_sale');
            }
            if (Schema::hasColumn('products', 'sale_start_date')) {
                $table->dropColumn('sale_start_date');
            }
            if (Schema::hasColumn('products', 'sale_end_date')) {
                $table->dropColumn('sale_end_date');
            }
            if (Schema::hasColumn('products', 'available_sizes')) {
                $table->dropColumn('available_sizes');
            }
            if (Schema::hasColumn('products', 'available_colors')) {
                $table->dropColumn('available_colors');
            }
        });
    }
};
