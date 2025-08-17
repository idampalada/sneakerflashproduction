<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shopping Cart Table (for both logged in and guest users)
        Schema::create('shopping_cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Tidak pakai constraint
            $table->string('session_id')->nullable(); // For guest users
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->json('product_options')->nullable(); // PostgreSQL JSON - size, color, etc.
            $table->timestamps();
            
            // PostgreSQL Safe Indexes
            $table->index('user_id');
            $table->index('session_id');
            $table->index('product_id');
            $table->unique(['user_id', 'product_id'], 'unique_user_product_cart');
            $table->unique(['session_id', 'product_id'], 'unique_session_product_cart');
        });

        // Coupons Table
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Coupon Configuration
            $table->string('type'); // percentage, fixed_amount, free_shipping
            $table->decimal('value', 10, 2); // percentage or amount
            $table->decimal('minimum_amount', 12, 2)->nullable();
            $table->decimal('maximum_discount', 12, 2)->nullable();
            
            // Usage Limits
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            
            // Status & Validity
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Applicable Products/Categories - PostgreSQL JSON
            $table->json('applicable_categories')->nullable();
            $table->json('applicable_products')->nullable();
            
            $table->timestamps();
            
            // PostgreSQL Safe Indexes
            $table->index('code');
            $table->index(['is_active', 'starts_at', 'expires_at']);
        });

        // Wishlists Table
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Tidak pakai constraint
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();
            
            // PostgreSQL Safe Indexes
            $table->index('user_id');
            $table->index('product_id');
            $table->unique(['user_id', 'product_id'], 'unique_user_product_wishlist');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('shopping_cart');
    }
};