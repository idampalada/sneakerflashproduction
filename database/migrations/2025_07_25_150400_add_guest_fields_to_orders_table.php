<?php
// File: database/migrations/add_guest_fields_to_orders_table.php
// Jalankan: php artisan make:migration add_guest_fields_to_orders_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Make user_id nullable untuk guest checkout
            $table->foreignId('user_id')->nullable()->change();
            
            // Add guest customer information fields
            $table->string('customer_name')->nullable()->after('user_id');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('customer_phone')->nullable()->after('customer_email');
            
            // Add payment token fields
            $table->text('payment_token')->nullable()->after('payment_status');
            $table->string('payment_url')->nullable()->after('payment_token');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->dropColumn([
                'customer_name',
                'customer_email', 
                'customer_phone',
                'payment_token',
                'payment_url'
            ]);
        });
    }
};

// ATAU jika migration sudah ada, update langsung di file yang sudah ada:
// File: database/migrations/2025_07_25_042347_create_sneakerflash_tables.php

/*
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_number', 100)->unique();
    $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // ✅ UBAH: nullable
    
    // ✅ ADD: Guest customer fields
    $table->string('customer_name')->nullable();
    $table->string('customer_email')->nullable();
    $table->string('customer_phone')->nullable();
    
    $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])
          ->default('pending');
    
    // Pricing
    $table->decimal('subtotal', 15, 2);
    $table->decimal('tax_amount', 15, 2)->default(0);
    $table->decimal('shipping_amount', 15, 2)->default(0);
    $table->decimal('discount_amount', 15, 2)->default(0);
    $table->decimal('total_amount', 15, 2);
    $table->char('currency', 3)->default('IDR');
    
    // Addresses (JSON)
    $table->json('shipping_address');
    $table->json('billing_address');
    
    // Payment
    $table->string('payment_method')->nullable();
    $table->enum('payment_status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])
          ->default('pending');
          
    // ✅ ADD: Payment gateway fields
    $table->text('payment_token')->nullable();
    $table->string('payment_url')->nullable();
    
    // Shipping
    $table->string('tracking_number')->nullable();
    $table->timestamp('shipped_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    
    // Additional
    $table->text('notes')->nullable();
    $table->timestamps();
    
    // Indexes
    $table->index(['user_id']);
    $table->index(['status']);
    $table->index(['payment_status']);
    $table->index(['order_number']);
    $table->index(['customer_email']); // ✅ ADD: Index for guest orders
});
*/