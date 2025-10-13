<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 100)->unique();
            
            // Customer Information (Support both registered & guest users)
            $table->unsignedBigInteger('user_id')->nullable(); // Tidak pakai constraint
            $table->string('customer_name')->nullable(); // For guest checkout
            $table->string('customer_email')->nullable(); // For guest checkout
            $table->string('customer_phone')->nullable(); // For guest checkout
            
            // Order Status
            $table->string('status')->default('pending'); // pending, processing, shipped, delivered, cancelled, refunded
            
            // Pricing - PostgreSQL decimal
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->char('currency', 3)->default('IDR');
            
            // Addresses - PostgreSQL JSON
            $table->json('shipping_address'); // Complete shipping address
            $table->json('billing_address'); // Complete billing address
            $table->json('store_origin')->nullable(); // Store/warehouse origin info
            
            // Payment Information
            $table->string('payment_method')->nullable(); // COD, Bank Transfer, E-wallet, etc
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->text('payment_token')->nullable(); // Payment gateway token
            $table->string('payment_url')->nullable(); // Payment gateway URL
            
            // Shipping Information
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Additional Information
            $table->text('notes')->nullable(); // Customer or admin notes
            $table->json('meta_data')->nullable(); // PostgreSQL JSON - any additional data
            
            $table->timestamps();
            
            // PostgreSQL Safe Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('order_number');
            $table->index('customer_email');
            $table->index('created_at');
        });

        // Order Items Table
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->unsignedBigInteger('product_id')->nullable(); // Tidak pakai constraint
            
            // Product Information (snapshot at time of order)
            $table->string('product_name'); // Store name in case product deleted
            $table->string('product_sku')->nullable();
            $table->decimal('product_price', 12, 2); // Price at time of order
            $table->integer('quantity');
            $table->decimal('total_price', 15, 2); // quantity * product_price
            
            // Product Snapshot - Store complete product details at time of order
            $table->json('product_snapshot')->nullable(); // PostgreSQL JSON
            
            $table->timestamps();
            
            // PostgreSQL Safe Indexes
            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};