<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            
            // Category & Brand
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('brand')->nullable();
            $table->string('sku')->nullable()->unique();
            
            // Gender & Product Type for Menu Classification - NO CONSTRAINTS
            $table->json('gender_target')->nullable(); // ['mens', 'womens', 'kids']
            $table->string('product_type')->nullable(); // lifestyle_casual, running, basketball, etc
            
            // Pricing
            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            
            // Inventory
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->decimal('weight', 8, 2)->nullable(); // in kg
            
            // Product Media & Features - PostgreSQL JSON
            $table->json('images')->nullable(); // Array of image paths
            $table->json('features')->nullable(); // Array of product features
            $table->json('specifications')->nullable(); // Key-value pairs
            $table->json('available_sizes')->nullable(); // Array of sizes
            $table->json('available_colors')->nullable(); // Array of colors
            
            // Status & Visibility
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_featured_sale')->default(false);
            $table->timestamp('published_at')->nullable();
            
            // Sale Management
            $table->date('sale_start_date')->nullable();
            $table->date('sale_end_date')->nullable();
            
            // SEO & Search - PostgreSQL JSON
            $table->json('search_keywords')->nullable(); // Array of keywords
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            
            // Additional Data
            $table->json('dimensions')->nullable(); // {length, width, height}
            $table->json('meta_data')->nullable(); // Any additional data
            
            $table->timestamps();
            
            // PostgreSQL Safe Indexes - No Constraints
            $table->index(['category_id', 'is_active']);
            $table->index(['is_active', 'is_featured']);
            $table->index(['is_active', 'published_at']);
            $table->index('brand');
            $table->index('sku');
            $table->index('slug');
            $table->index('product_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
