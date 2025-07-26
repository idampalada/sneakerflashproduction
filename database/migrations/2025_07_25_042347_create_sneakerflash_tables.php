<?php
// File: database/migrations/2025_01_XX_create_sneakerflash_tables.php
// PostgreSQL Compatible Migration

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Categories Table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('image')->nullable();
            $table->json('meta_data')->nullable(); // PostgreSQL JSON type
            $table->timestamps();
            
            // PostgreSQL specific indexes
            $table->index('is_active');
            $table->index('sort_order');
            $table->index('slug');
        });

        // 2. Products Table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2); // PostgreSQL decimal
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->string('sku')->nullable()->unique();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('brand')->nullable();
            $table->json('images')->nullable(); // PostgreSQL JSON for image array
            $table->json('features')->nullable(); // PostgreSQL JSON for features array
            $table->json('specifications')->nullable(); // PostgreSQL JSON
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->decimal('weight', 8, 2)->nullable(); // in kg
            $table->json('dimensions')->nullable(); // PostgreSQL JSON {length, width, height}
            $table->timestamp('published_at')->nullable();
            $table->json('meta_data')->nullable(); // PostgreSQL JSON for SEO, etc.
            $table->timestamps();
            
            // PostgreSQL specific indexes
            $table->index(['category_id', 'is_active']);
            $table->index(['is_active', 'is_featured']);
            $table->index(['is_active', 'published_at']);
            $table->index('brand');
            $table->index('sku');
            $table->index('slug');
        });

        // 3. Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 100)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Guest customer fields
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            
            $table->string('status')->default('pending'); // PostgreSQL enum alternative
            
            // Pricing with PostgreSQL decimal
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->char('currency', 3)->default('IDR');
            
            // Addresses as JSON (PostgreSQL native)
            $table->json('shipping_address');
            $table->json('billing_address');
            
            // Payment
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');
            $table->text('payment_token')->nullable();
            $table->string('payment_url')->nullable();
            
            // Shipping
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Additional
            $table->text('notes')->nullable();
            $table->json('meta_data')->nullable(); // PostgreSQL JSON
            $table->timestamps();
            
            // PostgreSQL specific indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('order_number');
            $table->index('customer_email');
            $table->index('created_at');
        });

        // 4. Order Items Table
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('product_name'); // Store name in case product deleted
            $table->string('product_sku')->nullable();
            $table->decimal('product_price', 12, 2); // Price at time of order
            $table->integer('quantity');
            $table->decimal('total_price', 15, 2); // quantity * product_price
            $table->json('product_snapshot')->nullable(); // PostgreSQL JSON - store product details
            $table->timestamps();
            
            // PostgreSQL specific indexes
            $table->index(['order_id']);
            $table->index(['product_id']);
        });

        // 5. Shopping Cart Table (session-based alternative)
        Schema::create('shopping_cart', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id')->nullable(); // For guest users
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->json('product_options')->nullable(); // PostgreSQL JSON - size, color, etc.
            $table->timestamps();
            
            // PostgreSQL specific indexes
            $table->index(['user_id']);
            $table->index(['session_id']);
            $table->index(['product_id']);
            $table->unique(['user_id', 'product_id'], 'unique_user_product_cart');
            $table->unique(['session_id', 'product_id'], 'unique_session_product_cart');
        });

        // 6. Coupons Table
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // percentage, fixed_amount, free_shipping
            $table->decimal('value', 10, 2); // percentage or amount
            $table->decimal('minimum_amount', 12, 2)->nullable();
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('applicable_categories')->nullable(); // PostgreSQL JSON
            $table->json('applicable_products')->nullable(); // PostgreSQL JSON
            $table->timestamps();
            
            // PostgreSQL specific indexes
            $table->index('code');
            $table->index(['is_active', 'starts_at', 'expires_at']);
        });

        // 7. Wishlists Table
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();
            
            // PostgreSQL specific indexes
            $table->index(['user_id']);
            $table->index(['product_id']);
            $table->unique(['user_id', 'product_id'], 'unique_user_product_wishlist');
        });
    }

    public function down(): void
    {
        // Drop tables in reverse order (PostgreSQL foreign key constraints)
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('shopping_cart');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};