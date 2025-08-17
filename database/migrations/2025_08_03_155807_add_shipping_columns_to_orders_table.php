<?php

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
        Schema::table('orders', function (Blueprint $table) {
            // Cek apakah kolom sudah ada sebelum menambahkan
            if (!Schema::hasColumn('orders', 'shipping_postal_code')) {
                $table->string('shipping_postal_code', 10)->nullable()->after('shipping_destination_label');
            }
            
            if (!Schema::hasColumn('orders', 'shipping_destination_id')) {
                $table->string('shipping_destination_id')->nullable()->after('shipping_address');
            }
            
            if (!Schema::hasColumn('orders', 'shipping_destination_label')) {
                $table->text('shipping_destination_label')->nullable()->after('shipping_destination_id');
            }
            
            // Tambahkan kolom lain yang mungkin dibutuhkan
            if (!Schema::hasColumn('orders', 'snap_token')) {
                $table->text('snap_token')->nullable()->after('payment_method');
            }
            
            if (!Schema::hasColumn('orders', 'payment_response')) {
                $table->json('payment_response')->nullable()->after('snap_token');
            }
            
            // Update existing columns if needed
            $table->decimal('total_amount', 12, 2)->change();
            $table->decimal('subtotal', 12, 2)->change();
            $table->decimal('tax_amount', 12, 2)->change();
            $table->decimal('shipping_cost', 12, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_postal_code',
                'shipping_destination_id', 
                'shipping_destination_label',
                'snap_token',
                'payment_response'
            ]);
        });
    }
};