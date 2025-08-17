<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            
            // Transaction type: earned, redeemed, expired, adjustment
            $table->enum('type', ['earned', 'redeemed', 'expired', 'adjustment']);
            
            // Points amount (positive for earned/adjustment, negative for redeemed)
            $table->decimal('amount', 15, 2);
            
            // Description of the transaction
            $table->text('description')->nullable();
            
            // Reference (order number, redemption code, etc.)
            $table->string('reference')->nullable();
            
            // Balance tracking
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index('type');
            $table->index('reference');
        });
        
        // PostgreSQL specific: Add comments to columns
        DB::statement("COMMENT ON TABLE points_transactions IS 'Track all points transactions for users'");
        DB::statement("COMMENT ON COLUMN points_transactions.type IS 'Type of transaction: earned, redeemed, expired, adjustment'");
        DB::statement("COMMENT ON COLUMN points_transactions.amount IS 'Points amount (positive for earned, negative for redeemed)'");
        DB::statement("COMMENT ON COLUMN points_transactions.balance_before IS 'User points balance before this transaction'");
        DB::statement("COMMENT ON COLUMN points_transactions.balance_after IS 'User points balance after this transaction'");
        DB::statement("COMMENT ON COLUMN points_transactions.reference IS 'Reference like order number, redemption code, etc.'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_transactions');
    }
};