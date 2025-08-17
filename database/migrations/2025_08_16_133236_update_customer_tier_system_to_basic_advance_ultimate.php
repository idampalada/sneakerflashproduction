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
        // Update enum values for customer_tier column (PostgreSQL specific)
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_customer_tier_check");
        
        // Update the column to new tier values
        DB::statement("
            ALTER TABLE users 
            ALTER COLUMN customer_tier TYPE VARCHAR(20) USING customer_tier::text,
            ALTER COLUMN customer_tier SET DEFAULT 'basic'
        ");
        
        // Update existing data
        DB::statement("
            UPDATE users SET customer_tier = 
            CASE 
                WHEN customer_tier = 'new' THEN 'basic'
                WHEN customer_tier = 'bronze' THEN 'basic'
                WHEN customer_tier = 'silver' THEN 'advance'
                WHEN customer_tier = 'gold' THEN 'ultimate'
                WHEN customer_tier = 'platinum' THEN 'ultimate'
                ELSE 'basic'
            END
        ");
        
        // Add new constraint for the updated enum values
        DB::statement("
            ALTER TABLE users 
            ADD CONSTRAINT users_customer_tier_check 
            CHECK (customer_tier IN ('basic', 'advance', 'ultimate'))
        ");
        
        // Add new columns for tier management and points system
        Schema::table('users', function (Blueprint $table) {
            // Add spending period tracking (for 6-month evaluation)
            $table->decimal('spending_6_months', 15, 2)->default(0)->after('customer_tier');
            $table->timestamp('tier_period_start')->nullable()->after('spending_6_months');
            $table->timestamp('last_tier_evaluation')->nullable()->after('tier_period_start');
            
            // Add points system
            $table->decimal('points_balance', 15, 2)->default(0)->after('last_tier_evaluation');
            $table->decimal('total_points_earned', 15, 2)->default(0)->after('points_balance');
            $table->decimal('total_points_redeemed', 15, 2)->default(0)->after('total_points_earned');
            
            // Add indexes for performance
            $table->index('spending_6_months');
            $table->index('tier_period_start');
            $table->index('points_balance');
        });
        
        // Update comment for customer_tier column
        DB::statement("COMMENT ON COLUMN users.customer_tier IS 'Customer tier: basic, advance, ultimate'");
        DB::statement("COMMENT ON COLUMN users.spending_6_months IS 'Total spending in current 6-month period'");
        DB::statement("COMMENT ON COLUMN users.tier_period_start IS 'Start date of current tier evaluation period'");
        DB::statement("COMMENT ON COLUMN users.last_tier_evaluation IS 'Last time tier was evaluated'");
        DB::statement("COMMENT ON COLUMN users.points_balance IS 'Current available points balance'");
        DB::statement("COMMENT ON COLUMN users.total_points_earned IS 'Total points earned from purchases'");
        DB::statement("COMMENT ON COLUMN users.total_points_redeemed IS 'Total points redeemed'");
        
        // Initialize tier_period_start for existing users
        DB::statement("UPDATE users SET tier_period_start = created_at WHERE tier_period_start IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove new columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['users_spending_6_months_index']);
            $table->dropIndex(['users_tier_period_start_index']);
            $table->dropIndex(['users_points_balance_index']);
            
            $table->dropColumn([
                'spending_6_months',
                'tier_period_start', 
                'last_tier_evaluation',
                'points_balance',
                'total_points_earned',
                'total_points_redeemed'
            ]);
        });
        
        // Revert customer_tier back to original enum
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_customer_tier_check");
        
        DB::statement("
            UPDATE users SET customer_tier = 
            CASE 
                WHEN customer_tier = 'basic' THEN 'bronze'
                WHEN customer_tier = 'advance' THEN 'silver'
                WHEN customer_tier = 'ultimate' THEN 'gold'
                ELSE 'new'
            END
        ");
        
        DB::statement("
            ALTER TABLE users 
            ALTER COLUMN customer_tier TYPE VARCHAR(20) USING customer_tier::text,
            ALTER COLUMN customer_tier SET DEFAULT 'new'
        ");
        
        DB::statement("
            ALTER TABLE users 
            ADD CONSTRAINT users_customer_tier_check 
            CHECK (customer_tier IN ('new', 'bronze', 'silver', 'gold', 'platinum'))
        ");
    }
};