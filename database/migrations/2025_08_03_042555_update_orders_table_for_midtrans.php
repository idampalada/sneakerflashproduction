<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cek existing columns dulu
        $columns = Schema::getColumnListing('orders');
        
        // 1. Handle shipping_cost/shipping_amount conflict
        if (in_array('shipping_amount', $columns) && !in_array('shipping_cost', $columns)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->renameColumn('shipping_amount', 'shipping_cost');
            });
        } elseif (in_array('shipping_amount', $columns) && in_array('shipping_cost', $columns)) {
            // Both exist, drop shipping_amount
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('shipping_amount');
            });
        }
        
        // 2. Add missing columns one by one
        if (!in_array('shipping_destination_id', $columns)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('shipping_destination_id')->nullable();
            });
        }
        
        if (!in_array('shipping_destination_label', $columns)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('shipping_destination_label')->nullable();
            });
        }
        
        if (!in_array('shipping_postal_code', $columns)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('shipping_postal_code', 10)->nullable();
            });
        }
        
        if (!in_array('snap_token', $columns)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('snap_token')->nullable();
            });
        }
        
        if (!in_array('payment_response', $columns)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->jsonb('payment_response')->nullable();
            });
        }
        
        // 3. Update decimal columns if needed
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('total_amount', 15, 2)->change();
                $table->decimal('subtotal', 15, 2)->change();
                $table->decimal('tax_amount', 15, 2)->nullable()->change();
                $table->decimal('shipping_cost', 15, 2)->change();
            });
        } catch (\Exception $e) {
            // Skip if error
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_destination_id',
                'shipping_destination_label',
                'shipping_postal_code',
                'snap_token',
                'payment_response'
            ]);
        });
    }
};