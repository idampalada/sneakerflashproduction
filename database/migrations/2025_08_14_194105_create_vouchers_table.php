<?php

// =====================================
// 1. MIGRATION - CREATE VOUCHERS TABLE
// File: database/migrations/2025_01_01_000001_create_vouchers_table.php
// =====================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            
            // Sync metadata
            $table->string('sync_status', 20)->default('synced'); // synced, pending, error
            $table->integer('spreadsheet_row_id')->nullable();
            
            // Core voucher data (sesuai spreadsheet)
            $table->string('code_product')->default('All product');
            $table->string('voucher_code', 100)->unique();
            $table->string('name_voucher');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('min_purchase', 12, 2)->default(0.00);
            $table->integer('quota')->default(0);
            $table->integer('claim_per_customer')->default(1);
            $table->enum('voucher_type', ['NOMINAL', 'PERCENT']);
            $table->string('value', 50); // "Rp50.000" atau "5%"
            $table->decimal('discount_max', 12, 2)->default(0.00);
            $table->string('category_customer', 100)->default('all customer');
            
            // Management fields
            $table->boolean('is_active')->default(true);
            $table->integer('total_used')->default(0);
            $table->integer('remaining_quota')->storedAs('quota - total_used');
            
            // Indexes
            $table->index(['voucher_code']);
            $table->index(['is_active']);
            $table->index(['start_date', 'end_date']);
            $table->index(['category_customer']);
            $table->index(['sync_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vouchers');
    }
};