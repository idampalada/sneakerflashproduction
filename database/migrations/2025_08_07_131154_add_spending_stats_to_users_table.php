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
            // Add spending statistics columns - PostgreSQL specific
            $table->decimal('total_spent', 15, 2)->default(0)->after('birthdate');
            $table->integer('total_orders')->default(0)->after('total_spent');
            $table->timestamp('spending_updated_at')->nullable()->after('total_orders');
            
            // Add indexes for better performance - PostgreSQL syntax
            $table->index('total_spent');
            $table->index('total_orders');
            $table->index(['total_spent', 'total_orders']);
        });
        
        // PostgreSQL specific: Add comment to columns
        DB::statement("COMMENT ON COLUMN users.total_spent IS 'Total amount spent by user from paid orders'");
        DB::statement("COMMENT ON COLUMN users.total_orders IS 'Total number of paid orders by user'");
        DB::statement("COMMENT ON COLUMN users.spending_updated_at IS 'Last time spending stats were updated'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['users_total_spent_index']);
            $table->dropIndex(['users_total_orders_index']);
            $table->dropIndex(['users_total_spent_total_orders_index']);
            
            // Drop columns
            $table->dropColumn([
                'total_spent',
                'total_orders', 
                'spending_updated_at'
            ]);
        });
    }
};