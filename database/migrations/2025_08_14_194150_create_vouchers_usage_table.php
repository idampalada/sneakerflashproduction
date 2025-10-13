<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('voucher_usage', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('voucher_id')->constrained()->onDelete('cascade');
            $table->string('customer_id', 100);
            $table->string('customer_email');
            $table->string('order_id', 100);
            $table->decimal('discount_amount', 12, 2);
            $table->decimal('order_total', 12, 2);
            $table->timestamp('used_at');
            $table->timestamps();
            
            // Prevent duplicate usage per customer per voucher
            $table->unique(['voucher_id', 'customer_id']);
            
            // Indexes
            $table->index(['voucher_id']);
            $table->index(['customer_id']);
            $table->index(['used_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('voucher_usage');
    }
};