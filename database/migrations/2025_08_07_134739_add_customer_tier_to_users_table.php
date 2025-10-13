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
        Schema::table('users', function (Blueprint $table) {
            // Add customer tier column
            $table->enum('customer_tier', ['new', 'bronze', 'silver', 'gold', 'platinum'])
                  ->default('new')
                  ->after('spending_updated_at');
            
            // Add index for fast tier-based queries
            $table->index('customer_tier');
        });
        
        // PostgreSQL specific: Add comment to column
        DB::statement("COMMENT ON COLUMN users.customer_tier IS 'Customer tier based on total spending: new, bronze, silver, gold, platinum'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex(['users_customer_tier_index']);
            
            // Drop column
            $table->dropColumn('customer_tier');
        });
    }
};