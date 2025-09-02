<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek apakah tabel webhook_events sudah ada
        if (!Schema::hasTable('webhook_events')) {
            // Buat tabel webhook_events terlebih dahulu
            Schema::create('webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('source', 50); // ginee, shopee, tokopedia, etc
                $table->string('entity', 100); // product, order, stock, etc
                $table->string('action', 100); // created, updated, deleted, etc
                $table->jsonb('payload'); // PostgreSQL JSONB
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                
                // Basic indexes
                $table->index(['source', 'entity'], 'webhook_events_source_entity_idx');
                $table->index('action', 'webhook_events_action_idx');
                $table->index('created_at', 'webhook_events_created_at_idx');
            });
        }
        
        // Tambahkan kolom tambahan untuk Ginee
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

        // Add additional indexes for performance (PostgreSQL safe)
        try {
            Schema::table('webhook_events', function (Blueprint $table) {
                if (!$this->indexExists('webhook_events_event_type_idx')) {
                    $table->index('event_type', 'webhook_events_event_type_idx');
                }
                
                if (!$this->indexExists('webhook_events_processed_idx')) {
                    $table->index('processed', 'webhook_events_processed_idx');
                }
                
                if (!$this->indexExists('webhook_events_retry_count_idx')) {
                    $table->index('retry_count', 'webhook_events_retry_count_idx');
                }
            });
        } catch (\Exception $e) {
            // Continue if index creation fails
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('webhook_events')) {
            return; // Nothing to rollback
        }
        
        Schema::table('webhook_events', function (Blueprint $table) {
            // Drop indexes first (PostgreSQL)
            try {
                $table->dropIndex('webhook_events_event_type_idx');
                $table->dropIndex('webhook_events_processed_idx'); 
                $table->dropIndex('webhook_events_retry_count_idx');
            } catch (\Exception $e) {
                // Ignore errors
            }
            
            // Drop added columns
            $columnsToRemove = [
                'event_type',
                'ip_address', 
                'user_agent',
                'headers',
                'processed',
                'processing_result',
                'retry_count'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('webhook_events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $result = $connection->select("
                SELECT 1 FROM pg_indexes 
                WHERE indexname = ? AND tablename = 'webhook_events'
            ", [$indexName]);
            
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
};