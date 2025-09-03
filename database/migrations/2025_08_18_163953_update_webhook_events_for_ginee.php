<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            // Add missing columns for better webhook tracking
            if (!Schema::hasColumn('webhook_events', 'event_type')) {
                $table->string('event_type', 100)->nullable()->after('action');
            }
            
            if (!Schema::hasColumn('webhook_events', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('payload');
            }
            
            if (!Schema::hasColumn('webhook_events', 'user_agent')) {
                $table->string('user_agent')->nullable()->after('ip_address');
            }
            
            if (!Schema::hasColumn('webhook_events', 'headers')) {
                $table->jsonb('headers')->nullable()->after('user_agent');
            }
            
            if (!Schema::hasColumn('webhook_events', 'processed')) {
                $table->boolean('processed')->default(false)->after('headers');
            }
            
            if (!Schema::hasColumn('webhook_events', 'processing_result')) {
                $table->text('processing_result')->nullable()->after('processed_at');
            }
            
            if (!Schema::hasColumn('webhook_events', 'retry_count')) {
                $table->integer('retry_count')->default(0)->after('processing_result');
            }
        });

        // Add indexes for better performance
        try {
            Schema::table('webhook_events', function (Blueprint $table) {
                if (!$this->indexExists('webhook_events', 'webhook_events_event_type_idx')) {
                    $table->index('event_type', 'webhook_events_event_type_idx');
                }
                
                if (!$this->indexExists('webhook_events', 'webhook_events_processed_idx')) {
                    $table->index('processed', 'webhook_events_processed_idx');
                }
                
                if (!$this->indexExists('webhook_events', 'webhook_events_source_entity_idx')) {
                    $table->index(['source', 'entity'], 'webhook_events_source_entity_idx');
                }
                
                if (!$this->indexExists('webhook_events', 'webhook_events_retry_count_idx')) {
                    $table->index('retry_count', 'webhook_events_retry_count_idx');
                }
            });
        } catch (\Exception $e) {
            // Continue if index creation fails
        }
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            // Drop indexes first
            try {
                $table->dropIndex('webhook_events_event_type_idx');
                $table->dropIndex('webhook_events_processed_idx');
                $table->dropIndex('webhook_events_source_entity_idx');
                $table->dropIndex('webhook_events_retry_count_idx');
            } catch (\Exception $e) {
                // Ignore errors
            }
            
            // Drop columns that we added
            $newColumns = [
                'event_type',
                'ip_address',
                'user_agent', 
                'headers',
                'processed',
                'processing_result',
                'retry_count'
            ];
            
            foreach ($newColumns as $column) {
                if (Schema::hasColumn('webhook_events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            $connection = Schema::getConnection();
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $doctrineSchemaManager->listTableIndexes($table);
            return isset($indexes[$index]);
        } catch (\Exception $e) {
            return false;
        }
    }
};