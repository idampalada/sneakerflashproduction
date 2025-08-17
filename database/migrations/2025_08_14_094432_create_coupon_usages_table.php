<?php

// File: database/migrations/2024_01_15_000002_create_coupon_usages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('discount_amount', 12, 2);
            $table->timestamp('used_at');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'coupon_id']);
            $table->index(['coupon_id', 'used_at']);
            $table->index(['order_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon_usages');
    }
};