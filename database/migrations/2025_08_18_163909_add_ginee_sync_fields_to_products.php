<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Ginee sync tracking fields
            if (!Schema::hasColumn('products', 'ginee_last_sync')) {
                $table->timestamp('ginee_last_sync')->nullable()->after('updated_at');
            }
            
            if (!Schema::hasColumn('products', 'ginee_sync_status')) {
                $table->enum('ginee_sync_status', ['pending', 'synced', 'error', 'disabled'])
                      ->default('pending')->after('ginee_last_sync');
            }
            
            if (!Schema::hasColumn('products', 'ginee_product_id')) {
                $table->string('ginee_product_id')->nullable()->after('ginee_sync_status');
            }
            
            if (!Schema::hasColumn('products', 'ginee_data')) {
                $table->jsonb('ginee_data')->nullable()->after('ginee_product_id');
            }
            
            if (!Schema::hasColumn('products', 'ginee_sync_error')) {
                $table->text('ginee_sync_error')->nullable()->after('ginee_data');
            }
        });

        // Add indexes for performance
        try {
            Schema::table('products', function (Blueprint $table) {
                $table->index('ginee_last_sync', 'products_ginee_last_sync_idx');
                $table->index('ginee_sync_status', 'products_ginee_sync_status_idx');
                $table->index('ginee_product_id', 'products_ginee_product_id_idx');
                $table->index(['ginee_sync_status', 'ginee_last_sync'], 'products_ginee_sync_composite_idx');
            });
        } catch (\Exception $e) {
            // Indexes might already exist or fail, continue
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop indexes first
            try {
                $table->dropIndex('products_ginee_last_sync_idx');
                $table->dropIndex('products_ginee_sync_status_idx');
                $table->dropIndex('products_ginee_product_id_idx');
                $table->dropIndex('products_ginee_sync_composite_idx');
            } catch (\Exception $e) {
                // Ignore if indexes don't exist
            }
            
            // Drop columns if they exist
            $gineeColumns = [
                'ginee_last_sync',
                'ginee_sync_status', 
                'ginee_product_id',
                'ginee_data',
                'ginee_sync_error'
            ];
            
            foreach ($gineeColumns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};