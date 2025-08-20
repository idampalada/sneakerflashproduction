<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ginee_sync_logs', function (Blueprint $table) {
            // Kolom untuk individual sync operations (seperti yang digunakan di model)
            $table->string('operation_type')->nullable(); // sync, push
            $table->string('sku')->nullable();
            $table->string('product_name')->nullable();
            
            // Stock tracking
            $table->integer('old_stock')->nullable();
            $table->integer('old_warehouse_stock')->nullable();
            $table->integer('new_stock')->nullable();
            $table->integer('new_warehouse_stock')->nullable();
            
            // Details
            $table->text('message')->nullable();
            $table->jsonb('ginee_response')->nullable(); // PostgreSQL jsonb
            $table->string('transaction_id')->nullable();
            $table->string('method_used')->nullable();
            $table->string('initiated_by_user')->nullable(); // rename to avoid conflict
            
            // Operation settings
            $table->boolean('dry_run')->default(false);
            $table->integer('batch_size')->nullable();
            $table->string('session_id')->nullable();
            
            // Add regular indexes (bukan CONCURRENTLY untuk migration)
            $table->index('sku', 'ginee_sync_logs_sku_idx');
            $table->index('operation_type', 'ginee_sync_logs_operation_type_idx');
            $table->index('session_id', 'ginee_sync_logs_session_id_idx');
            $table->index(['operation_type', 'status'], 'ginee_sync_logs_op_status_idx');
        });
        
        // Add PostgreSQL comments for documentation
        DB::statement("COMMENT ON COLUMN ginee_sync_logs.operation_type IS 'Type of operation: sync (from Ginee), push (to Ginee)'");
        DB::statement("COMMENT ON COLUMN ginee_sync_logs.sku IS 'Product SKU being synced'");
        DB::statement("COMMENT ON COLUMN ginee_sync_logs.ginee_response IS 'Full response from Ginee API (JSONB)'");
        DB::statement("COMMENT ON COLUMN ginee_sync_logs.session_id IS 'Session identifier for grouping related operations'");
    }

    public function down(): void
    {
        Schema::table('ginee_sync_logs', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('ginee_sync_logs_sku_idx');
            $table->dropIndex('ginee_sync_logs_operation_type_idx');
            $table->dropIndex('ginee_sync_logs_session_id_idx');
            $table->dropIndex('ginee_sync_logs_op_status_idx');
            
            // Drop columns
            $table->dropColumn([
                'operation_type',
                'sku',
                'product_name',
                'old_stock',
                'old_warehouse_stock',
                'new_stock',
                'new_warehouse_stock',
                'message',
                'ginee_response',
                'transaction_id',
                'method_used',
                'initiated_by_user',
                'dry_run',
                'batch_size',
                'session_id'
            ]);
        });
    }
};