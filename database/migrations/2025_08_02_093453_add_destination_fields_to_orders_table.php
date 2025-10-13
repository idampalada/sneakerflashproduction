<?php
// File: database/migrations/2025_08_02_093453_add_destination_fields_to_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Check existing columns first
            $existingColumns = Schema::getColumnListing('orders');
            
            // Add destination fields for search-based approach if they don't exist
            if (!in_array('shipping_destination_id', $existingColumns)) {
                $table->string('shipping_destination_id')->nullable()->after('shipping_address');
            }
            
            if (!in_array('shipping_destination_label', $existingColumns)) {
                $table->text('shipping_destination_label')->nullable()->after('shipping_destination_id');
            }
            
            // Only modify columns that actually exist
            if (in_array('shipping_province_id', $existingColumns)) {
                $table->integer('shipping_province_id')->nullable()->change();
            }
            
            if (in_array('shipping_city_id', $existingColumns)) {
                $table->integer('shipping_city_id')->nullable()->change();
            }
            
            if (in_array('shipping_district_id', $existingColumns)) {
                $table->integer('shipping_district_id')->nullable()->change();
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $existingColumns = Schema::getColumnListing('orders');
            
            $columnsToDrop = [];
            
            if (in_array('shipping_destination_id', $existingColumns)) {
                $columnsToDrop[] = 'shipping_destination_id';
            }
            
            if (in_array('shipping_destination_label', $existingColumns)) {
                $columnsToDrop[] = 'shipping_destination_label';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};