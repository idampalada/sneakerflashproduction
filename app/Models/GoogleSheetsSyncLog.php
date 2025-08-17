<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GoogleSheetsSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_id',
        'spreadsheet_id',
        'sheet_name',
        'initiated_by',
        'started_at',
        'completed_at',
        'status',
        'total_rows',
        'processed_rows',
        'created_products',
        'updated_products',
        'deleted_products',
        'skipped_rows',
        'error_count',
        
        // Enhanced metrics
        'unique_sku_parents',
        'unique_skus',
        'products_with_variants',
        
        'sync_results',
        'error_details',
        'sync_options',
        'sku_mapping',
        'duration_seconds',
        'summary',
        'error_message',
        
        // Strategy tracking
        'sync_strategy',
        'clean_old_data',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        
        // PostgreSQL JSON casting
        'sync_results' => 'array',
        'error_details' => 'array',
        'sync_options' => 'array',
        'sku_mapping' => 'array',
        
        // Integer casts
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'created_products' => 'integer',
        'updated_products' => 'integer',
        'deleted_products' => 'integer',
        'skipped_rows' => 'integer',
        'error_count' => 'integer',
        'unique_sku_parents' => 'integer',
        'unique_skus' => 'integer',
        'products_with_variants' => 'integer',
        'duration_seconds' => 'integer', // FIXED: Always cast to integer
        
        // Boolean casts
        'clean_old_data' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (empty($log->sync_id)) {
                $log->sync_id = 'sync_' . Str::random(12) . '_' . now()->format('Ymd_His');
            }
            if (empty($log->started_at)) {
                $log->started_at = now();
            }
        });
    }

    /**
     * Scopes
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed')->where('error_count', 0);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeWithErrors($query)
    {
        return $query->where('error_count', '>', 0);
    }

    public function scopeSmartSync($query)
    {
        return $query->where('sync_strategy', 'smart_individual_sku');
    }

    /**
     * Accessors
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => '⏳ Pending',
            'running' => '🔄 Running',
            'completed' => $this->error_count > 0 ? '⚠️ Completed with Errors' : '✅ Completed',
            'failed' => '❌ Failed',
            default => '❓ Unknown'
        };
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_rows == 0) return 0;
        return round((($this->created_products + $this->updated_products) / $this->total_rows) * 100, 2);
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds || $this->duration_seconds <= 0) return 'N/A';
        
        $seconds = abs($this->duration_seconds); // Use absolute value
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }
        return "{$remainingSeconds}s";
    }

    public function getIsRunningAttribute(): bool
    {
        return in_array($this->status, ['pending', 'running']);
    }

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    public function getHasErrorsAttribute(): bool
    {
        return $this->error_count > 0;
    }

    // Smart sync specific accessors
    public function getSkuEfficiencyAttribute(): float
    {
        if ($this->unique_sku_parents == 0) return 0;
        return round(($this->unique_skus / $this->unique_sku_parents), 2);
    }

    public function getNetProductChangeAttribute(): int
    {
        return ($this->created_products + $this->updated_products) - $this->deleted_products;
    }

    public function getIsSmartSyncAttribute(): bool
    {
        return $this->sync_strategy === 'smart_individual_sku';
    }

    /**
     * Helper methods - FIXED DURATION CALCULATION
     */
    public function markAsStarted(): self
    {
        $this->update([
            'status' => 'running',
            'started_at' => now()
        ]);
        return $this;
    }

    public function markAsCompleted(array $results = []): self
    {
        $endTime = now();
        
        // FIXED: Proper duration calculation
        $duration = 0;
        if ($this->started_at) {
            $durationFloat = $endTime->diffInSeconds($this->started_at, false);
            $duration = max(0, (int) round(abs($durationFloat))); // Always positive integer
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => $endTime,
            'duration_seconds' => $duration,
            'sync_results' => $results,
            'summary' => $this->generateSummary($results)
        ]);
        return $this;
    }

    public function markAsFailed(string $errorMessage, array $errorDetails = []): self
    {
        $endTime = now();
        
        // FIXED: Proper duration calculation  
        $duration = 0;
        if ($this->started_at) {
            $durationFloat = $endTime->diffInSeconds($this->started_at, false);
            $duration = max(0, (int) round(abs($durationFloat))); // Always positive integer
        }

        $this->update([
            'status' => 'failed',
            'completed_at' => $endTime,
            'duration_seconds' => $duration,
            'error_message' => $errorMessage,
            'error_details' => $errorDetails
        ]);
        return $this;
    }

    public function updateStats(array $stats): self
    {
        $updateData = [];
        
        $allowedFields = [
            'total_rows', 'processed_rows', 'created_products', 'updated_products', 
            'deleted_products', 'skipped_rows', 'error_count', 'unique_sku_parents', 
            'unique_skus', 'products_with_variants', 'sku_mapping'
        ];

        foreach ($allowedFields as $field) {
            if (isset($stats[$field])) {
                // Ensure integer fields are properly cast
                if (in_array($field, ['total_rows', 'processed_rows', 'created_products', 'updated_products', 'deleted_products', 'skipped_rows', 'error_count', 'unique_sku_parents', 'unique_skus', 'products_with_variants'])) {
                    $updateData[$field] = (int) $stats[$field];
                } else {
                    $updateData[$field] = $stats[$field];
                }
            }
        }

        if (!empty($updateData)) {
            $this->update($updateData);
        }
        
        return $this;
    }

    private function generateSummary(array $results = []): string
    {
        $summary = [];
        
        if ($this->created_products > 0) {
            $summary[] = "Created {$this->created_products} products";
        }
        
        if ($this->updated_products > 0) {
            $summary[] = "Updated {$this->updated_products} products";
        }
        
        if ($this->deleted_products > 0) {
            $summary[] = "Deleted {$this->deleted_products} old products";
        }
        
        if ($this->skipped_rows > 0) {
            $summary[] = "Skipped {$this->skipped_rows} rows";
        }
        
        if ($this->error_count > 0) {
            $summary[] = "Encountered {$this->error_count} errors";
        }

        // Smart sync specific summary
        if ($this->is_smart_sync) {
            if ($this->unique_sku_parents > 0 && $this->unique_skus > 0) {
                $summary[] = "Processed {$this->unique_sku_parents} parent products into {$this->unique_skus} individual SKU products";
            }
            
            $netChange = $this->net_product_change;
            if ($netChange > 0) {
                $summary[] = "Net increase: +{$netChange} products";
            } elseif ($netChange < 0) {
                $summary[] = "Net decrease: {$netChange} products";
            } else {
                $summary[] = "No net change in product count";
            }
        }

        return implode(', ', $summary) ?: 'No changes made';
    }

    /**
     * Static factory methods
     */
    public static function createForSync(string $spreadsheetId, ?string $initiatedBy = null, array $options = []): self
    {
        return static::create([
            'spreadsheet_id' => $spreadsheetId,
            'sheet_name' => $options['sheet_name'] ?? 'Sheet1',
            'initiated_by' => $initiatedBy,
            'status' => 'pending',
            'sync_options' => $options,
            'sync_strategy' => $options['sync_strategy'] ?? 'individual_sku',
            'clean_old_data' => $options['clean_old_data'] ?? false,
            'started_at' => now()
        ]);
    }

    /**
     * Clean up old logs
     */
    public static function cleanup(int $keepDays = 30): int
    {
        return static::where('started_at', '<', now()->subDays($keepDays))->delete();
    }

    /**
     * Get recent sync statistics
     */
    public static function getRecentStats(int $days = 7): array
    {
        $logs = static::recent($days)->get();
        
        return [
            'total_syncs' => $logs->count(),
            'successful_syncs' => $logs->where('status', 'completed')->where('error_count', 0)->count(),
            'failed_syncs' => $logs->where('status', 'failed')->count(),
            'syncs_with_errors' => $logs->where('error_count', '>', 0)->count(),
            'total_products_created' => $logs->sum('created_products'),
            'total_products_updated' => $logs->sum('updated_products'),
            'total_products_deleted' => $logs->sum('deleted_products'),
            'total_errors' => $logs->sum('error_count'),
            'average_duration' => $logs->where('duration_seconds', '>', 0)->avg('duration_seconds'),
            
            // Smart sync metrics
            'smart_syncs' => $logs->where('sync_strategy', 'smart_individual_sku')->count(),
            'average_sku_efficiency' => $logs->where('unique_sku_parents', '>', 0)->avg('sku_efficiency'),
            'total_unique_skus_processed' => $logs->sum('unique_skus'),
            'total_sku_parents_processed' => $logs->sum('unique_sku_parents'),
            'net_product_change' => $logs->sum('created_products') + $logs->sum('updated_products') - $logs->sum('deleted_products'),
        ];
    }

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'initiated_by');
    }
}