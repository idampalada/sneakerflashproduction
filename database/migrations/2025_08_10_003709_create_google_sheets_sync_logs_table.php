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
        Schema::create('google_sheets_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id')->unique(); // UUID for each sync operation
            $table->string('spreadsheet_id'); // Google Sheets ID
            $table->string('sheet_name')->default('Sheet1');
            $table->string('initiated_by')->nullable(); // User who started sync
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('pending'); // pending, running, completed, failed
            
            // Statistics
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('created_products')->default(0);
            $table->integer('updated_products')->default(0);
            $table->integer('deleted_products')->default(0); // NEW: untuk track deleted products
            $table->integer('skipped_rows')->default(0);
            $table->integer('error_count')->default(0);
            
            // Enhanced Statistics for new logic
            $table->integer('unique_sku_parents')->default(0); // How many parent products
            $table->integer('unique_skus')->default(0); // How many individual SKUs
            $table->integer('products_with_variants')->default(0); // Products with multiple sizes
            
            // Results and errors (JSON for PostgreSQL)
            $table->json('sync_results')->nullable();
            $table->json('error_details')->nullable();
            $table->json('sync_options')->nullable(); // Options used for sync
            $table->json('sku_mapping')->nullable(); // Track SKU parent -> SKU mapping
            
            // Duration and performance
            $table->integer('duration_seconds')->nullable();
            $table->text('summary')->nullable();
            $table->text('error_message')->nullable(); // Main error if sync failed
            
            // Sync strategy tracking
            $table->string('sync_strategy')->default('individual_sku'); // individual_sku, grouped_variants, smart_individual_sku
            $table->boolean('clean_old_data')->default(false);
            
            // PostgreSQL specific indexes
            $table->index(['status', 'started_at']);
            $table->index(['spreadsheet_id', 'started_at']);
            $table->index('initiated_by');
            $table->index('sync_strategy');
            
            $table->timestamps();
        });
        
        // Add comments for PostgreSQL (optional but helpful)
        DB::statement("COMMENT ON TABLE google_sheets_sync_logs IS 'Logs for Google Sheets sync operations with detailed metrics'");
        DB::statement("COMMENT ON COLUMN google_sheets_sync_logs.sync_strategy IS 'Strategy used: individual_sku, grouped_variants, smart_individual_sku'");
        DB::statement("COMMENT ON COLUMN google_sheets_sync_logs.unique_sku_parents IS 'Number of unique SKU parent products in spreadsheet'");
        DB::statement("COMMENT ON COLUMN google_sheets_sync_logs.unique_skus IS 'Number of unique individual SKUs processed'");
        DB::statement("COMMENT ON COLUMN google_sheets_sync_logs.deleted_products IS 'Number of products deleted during smart sync'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_sheets_sync_logs');
    }
};