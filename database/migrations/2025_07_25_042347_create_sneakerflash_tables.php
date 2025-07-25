<?php
// database/migrations/xxxx_create_sneakerflash_tables.php

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
        // 1. Categories Table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });

        // 2. Products Table
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();
            $table->string('sku')->unique();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('brand')->nullable();
            $table->json('sizes')->nullable(); // ["38", "39", "40", "41", "42"]
            $table->json('colors')->nullable(); // ["Red", "Blue", "Black"]
            $table->json('images')->nullable(); // ["image1.jpg", "image2.jpg"]
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->decimal('weight', 8, 2)->nullable();
            $table->json('specifications')->nullable(); // {"material": "leather", "sole": "rubber"}
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Indexes untuk performa
            $table->index(['is_active', 'is_featured']);
            $table->index(['category_id', 'is_active']);
            $table->index('published_at');
            $table->index('brand');
        });

        // 3. Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('IDR');
            $table->json('shipping_address'); // {"name": "John", "address": "...", "city": "Jakarta"}
            $table->json('billing_address'); // Same structure as shipping
            $table->string('payment_method')->nullable(); // "credit_card", "bank_transfer", "e_wallet"
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('order_number');
            $table->index('payment_status');
        });

        // 4. Order Items Table
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('product_name'); // Snapshot untuk historical data
            $table->string('product_sku'); // Snapshot untuk historical data
            $table->json('product_options')->nullable(); // {"size": "42", "color": "Black"}
            $table->timestamps();
            
            $table->index(['order_id']);
            $table->index(['product_id']);
        });

        // 5. Shopping Cart Table
        Schema::create('shopping_cart', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->json('product_options')->nullable(); // {"size": "42", "color": "Black"}
            $table->timestamps();
            
            $table->index(['session_id']);
            $table->index(['user_id']);
            $table->index(['product_id']);
            
            // Unique constraint: one product variant per user/session
            $table->unique(['user_id', 'product_id', 'session_id'], 'unique_cart_item');
        });

        // 6. Product Reviews Table
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->integer('rating'); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->json('images')->nullable(); // Review images
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
            
            $table->index(['product_id', 'is_approved']);
            $table->index(['user_id']);
            $table->index('rating');
            
            // One review per user per product
            $table->unique(['product_id', 'user_id'], 'unique_user_product_review');
        });

        // 7. Coupons Table
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['fixed', 'percentage']); // Fixed amount or percentage
            $table->decimal('value', 10, 2); // Discount value
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Minimum order amount
            $table->decimal('maximum_discount', 10, 2)->nullable(); // Max discount for percentage type
            $table->integer('usage_limit')->nullable(); // Total usage limit
            $table->integer('usage_limit_per_user')->nullable(); // Per user limit
            $table->integer('used_count')->default(0);
            $table->datetime('starts_at');
            $table->datetime('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['code', 'is_active']);
            $table->index(['starts_at', 'expires_at']);
        });

        // 8. Coupon Usage Table
        Schema::create('coupon_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
            
            $table->index(['coupon_id']);
            $table->index(['user_id']);
            $table->index(['order_id']);
        });

        // 9. Wishlists Table
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['user_id']);
            $table->index(['product_id']);
            
            // One product per user in wishlist
            $table->unique(['user_id', 'product_id'], 'unique_user_product_wishlist');
        });

        // 10. Product Images Table (jika ingin terpisah dari JSON)
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order (karena foreign key constraints)
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('coupon_usage');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('shopping_cart');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};