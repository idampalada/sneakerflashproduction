<?php

// 📁 database/migrations/xxxx_xx_xx_add_change_column_to_ginee_sync_logs_table.php
// Run: php artisan make:migration add_change_column_to_ginee_sync_logs_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ginee_sync_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ginee_sync_logs', 'change')) {
                $table->integer('change')->nullable()->after('new_stock')->comment('Stock change (new_stock - old_stock)');
            }
            
            // Add index untuk query performance
            if (!Schema::hasColumn('ginee_sync_logs', 'session_id')) {
                $table->string('session_id')->nullable()->after('id')->index();
            }
            
            if (!Schema::hasColumn('ginee_sync_logs', 'dry_run')) {
                $table->boolean('dry_run')->default(false)->after('message')->index();
            }
            
            // Add composite index untuk query optimization
            $table->index(['session_id', 'created_at'], 'idx_session_created');
            $table->index(['sku', 'created_at'], 'idx_sku_created');
            $table->index(['dry_run', 'status'], 'idx_dryrun_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ginee_sync_logs', function (Blueprint $table) {
            $table->dropColumn(['change']);
            $table->dropIndex(['session_id', 'created_at']);
            $table->dropIndex(['sku', 'created_at']);
            $table->dropIndex(['dry_run', 'status']);
        });
    }
};