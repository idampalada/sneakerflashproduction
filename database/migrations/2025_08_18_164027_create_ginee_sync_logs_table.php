<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ginee_sync_logs')) {
            Schema::create('ginee_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['product_pull', 'stock_push', 'webhook', 'manual'])->index();
                $table->enum('status', ['started', 'completed', 'failed', 'cancelled'])->index();
                
                // Statistics
                $table->integer('items_processed')->default(0);
                $table->integer('items_successful')->default(0);
                $table->integer('items_failed')->default(0);
                $table->integer('items_skipped')->default(0);
                
                // Tracking
                $table->jsonb('parameters')->nullable(); // Input parameters
                $table->jsonb('summary')->nullable(); // Results summary
                $table->jsonb('errors')->nullable(); // Error details
                $table->text('error_message')->nullable();
                
                // Timing
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->integer('duration_seconds')->nullable();
                
                // Context
                $table->string('triggered_by')->nullable(); // user_id, cron, webhook, etc
                $table->string('batch_id')->nullable(); // For grouping related operations
                
                $table->timestamps();

                // Performance indexes
                $table->index(['type', 'status'], 'ginee_sync_logs_type_status_idx');
                $table->index('created_at', 'ginee_sync_logs_created_at_idx');
                $table->index('batch_id', 'ginee_sync_logs_batch_id_idx');
                $table->index(['status', 'started_at'], 'ginee_sync_logs_status_started_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ginee_sync_logs');
    }
};