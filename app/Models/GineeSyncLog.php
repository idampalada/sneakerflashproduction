<?php

// 🔧 UPDATED: app/Models/GineeSyncLog.php
// Add all missing columns to fillable array

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GineeSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        // Core fields
        'session_id',
        'type',
        'status', 
        'operation_type',           // ✅ ADDED - for tracking sync/push operations
        'method_used',              // ✅ ADDED - for tracking which method was used
        
        // Product information
        'sku',
        'product_name',
        
        // Stock tracking
        'old_stock',
        'new_stock',
        'old_warehouse_stock',      // ✅ ADDED - for warehouse stock tracking
        'new_warehouse_stock',      // ✅ ADDED - for warehouse stock tracking
        'change',
        
        // Messages and details
        'message',
        'error_message',
        'ginee_response',           // ✅ ADDED - for storing full Ginee API response
        'transaction_id',           // ✅ ADDED - for tracking transaction IDs
        
        // Operation settings
        'dry_run',
        'batch_size',               // ✅ ADDED - for tracking batch operations
        'initiated_by_user',        // ✅ ADDED - for tracking who initiated the operation
        
        // Metadata and timestamps
        'metadata',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'old_stock' => 'integer',
        'new_stock' => 'integer', 
        'old_warehouse_stock' => 'integer',      // ✅ ADDED
        'new_warehouse_stock' => 'integer',      // ✅ ADDED
        'change' => 'integer',
        'dry_run' => 'boolean',
        'ginee_response' => 'json',              // ✅ ADDED - for JSONB field
        'metadata' => 'json',
        'batch_size' => 'integer',               // ✅ ADDED
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ✅ Auto-calculate change jika tidak diset manual
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-calculate change jika belum diset
            if (is_null($model->change) && !is_null($model->old_stock) && !is_null($model->new_stock)) {
                $model->change = $model->new_stock - $model->old_stock;
            }
        });

        static::updating(function ($model) {
            // Auto-calculate change jika belum diset
            if (is_null($model->change) && !is_null($model->old_stock) && !is_null($model->new_stock)) {
                $model->change = $model->new_stock - $model->old_stock;
            }
        });
    }

    // ✅ Accessor untuk format change dengan warna
    protected function formattedChange(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->change == 0) {
                    return '0';
                }
                
                $prefix = $this->change > 0 ? '+' : '';
                return "{$prefix}{$this->change}";
            }
        );
    }

    // ✅ Accessor untuk status yang lebih deskriptif
    protected function displayStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->dry_run) {
                    if ($this->status === 'success' || $this->status === 'skipped') {
                        return $this->change == 0 ? 'Skipped (Dry Run)' : 'Would Update (Dry Run)';
                    }
                    return 'Dry Run ' . ucfirst($this->status);
                }
                return ucfirst($this->status);
            }
        );
    }

    // ✅ Accessor untuk method description
    protected function methodDescription(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->method_used) {
                    'stock_push' => 'Standard Stock Push',
                    'optimized_bulk' => 'Optimized Bulk Method',
                    'enhanced_fallback' => 'Enhanced Fallback',
                    'warehouse_inventory' => 'Warehouse Inventory API',
                    'master_products' => 'Master Products API',
                    default => ucfirst(str_replace('_', ' ', $this->method_used ?? 'Unknown'))
                };
            }
        );
    }

    // ✅ Scope untuk dry run
    public function scopeDryRun($query, $isDryRun = true)
    {
        return $query->where('dry_run', $isDryRun);
    }

    // ✅ Scope untuk session tertentu
    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // ✅ Scope untuk SKU tertentu
    public function scopeBySku($query, $sku)
    {
        return $query->where('sku', $sku);
    }

    // ✅ Scope untuk operation type
    public function scopeByOperation($query, $operationType)
    {
        return $query->where('operation_type', $operationType);
    }

    // ✅ Scope untuk method used
    public function scopeByMethod($query, $method)
    {
        return $query->where('method_used', $method);
    }

    // ✅ Scope untuk recent logs
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ✅ Generate session ID unik
    public static function generateSessionId(): string
    {
        return 'ginee_' . date('Ymd_His') . '_' . substr(md5(microtime()), 0, 8);
    }

    // ✅ Get latest session untuk type tertentu
    public static function getLatestSession($type = null): ?string
    {
        $query = static::query();
        
        if ($type) {
            $query->where('type', $type);
        }
        
        return $query->latest('created_at')
                    ->value('session_id');
    }

    // ✅ Get summary statistics untuk session
    public static function getSessionStats($sessionId): array
    {
        $logs = static::where('session_id', $sessionId)->get();
        
        return [
            'total' => $logs->count(),
            'successful' => $logs->where('status', 'success')->count(),
            'failed' => $logs->where('status', 'failed')->count(),
            'skipped' => $logs->where('status', 'skipped')->count(),
            'would_update' => $logs->where('status', 'success')->where('change', '!=', 0)->count(),
            'dry_run' => $logs->where('dry_run', true)->count() > 0,
            'methods_used' => $logs->pluck('method_used')->filter()->unique()->values()->toArray(),
            'operation_types' => $logs->pluck('operation_type')->filter()->unique()->values()->toArray(),
            'created_at' => $logs->min('created_at'),
            'completed_at' => $logs->max('created_at'),
            'duration_minutes' => $logs->count() > 0 ? 
                round($logs->max('created_at')->diffInMinutes($logs->min('created_at')), 2) : 0
        ];
    }

    // ✅ Get statistics untuk specific SKU
    public static function getSkuStats($sku, $days = 30): array
    {
        $logs = static::where('sku', $sku)
                     ->where('created_at', '>=', now()->subDays($days))
                     ->get();
        
        return [
            'total_attempts' => $logs->count(),
            'successful' => $logs->where('status', 'success')->count(),
            'failed' => $logs->where('status', 'failed')->count(),
            'skipped' => $logs->where('status', 'skipped')->count(),
            'dry_runs' => $logs->where('dry_run', true)->count(),
            'live_runs' => $logs->where('dry_run', false)->count(),
            'last_success' => $logs->where('status', 'success')->sortByDesc('created_at')->first()?->created_at,
            'last_failure' => $logs->where('status', 'failed')->sortByDesc('created_at')->first()?->created_at,
            'methods_used' => $logs->pluck('method_used')->filter()->unique()->values()->toArray(),
            'average_stock_change' => $logs->where('change', '!=', null)->avg('change') ?? 0,
            'total_stock_changes' => $logs->where('change', '!=', null)->sum('change') ?? 0
        ];
    }

    // ✅ Get daily statistics for dashboard
    public static function getDailyStats($days = 7): array
    {
        $stats = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->startOfDay();
            $nextDate = $date->copy()->addDay();
            
            $dayLogs = static::whereBetween('created_at', [$date, $nextDate])->get();
            
            $stats[] = [
                'date' => $date->format('Y-m-d'),
                'date_formatted' => $date->format('M d'),
                'total' => $dayLogs->count(),
                'successful' => $dayLogs->where('status', 'success')->count(),
                'failed' => $dayLogs->where('status', 'failed')->count(),
                'skipped' => $dayLogs->where('status', 'skipped')->count(),
                'dry_runs' => $dayLogs->where('dry_run', true)->count(),
                'live_runs' => $dayLogs->where('dry_run', false)->count(),
                'unique_skus' => $dayLogs->pluck('sku')->filter()->unique()->count(),
                'unique_sessions' => $dayLogs->pluck('session_id')->filter()->unique()->count()
            ];
        }
        
        return array_reverse($stats); // Most recent first
    }

    // ✅ Get method performance statistics
    public static function getMethodStats($days = 30): array
    {
        $logs = static::where('created_at', '>=', now()->subDays($days))
                     ->whereNotNull('method_used')
                     ->get();
        
        $methodStats = [];
        
        foreach ($logs->groupBy('method_used') as $method => $methodLogs) {
            $methodStats[] = [
                'method' => $method,
                'method_name' => static::getMethodDisplayName($method),
                'total_uses' => $methodLogs->count(),
                'success_rate' => $methodLogs->count() > 0 ? 
                    round(($methodLogs->where('status', 'success')->count() / $methodLogs->count()) * 100, 1) : 0,
                'successful' => $methodLogs->where('status', 'success')->count(),
                'failed' => $methodLogs->where('status', 'failed')->count(),
                'skipped' => $methodLogs->where('status', 'skipped')->count(),
                'average_stock_change' => $methodLogs->where('change', '!=', null)->avg('change') ?? 0,
                'last_used' => $methodLogs->sortByDesc('created_at')->first()?->created_at
            ];
        }
        
        // Sort by success rate descending
        return collect($methodStats)->sortByDesc('success_rate')->values()->toArray();
    }

    // ✅ Helper method untuk display name
    public static function getMethodDisplayName($method): string
    {
        return match($method) {
            'stock_push' => 'Standard Stock Push',
            'optimized_bulk' => 'Optimized Bulk Method',
            'enhanced_fallback' => 'Enhanced Fallback',
            'warehouse_inventory' => 'Warehouse Inventory API',
            'master_products' => 'Master Products API',
            'bulk_optimized' => 'Bulk Optimized',
            'individual_search' => 'Individual Search',
            default => ucfirst(str_replace('_', ' ', $method ?? 'Unknown'))
        };
    }

    // ✅ Cleanup old logs (untuk maintenance)
    public static function cleanupOldLogs($daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        return static::where('created_at', '<', $cutoffDate)->delete();
    }

    // ✅ Get recent failed SKUs untuk debugging
    public static function getRecentFailedSkus($limit = 10, $days = 7): array
    {
        return static::where('status', 'failed')
                    ->where('created_at', '>=', now()->subDays($days))
                    ->whereNotNull('sku')
                    ->groupBy('sku')
                    ->selectRaw('sku, COUNT(*) as failure_count, MAX(created_at) as last_failure')
                    ->orderByDesc('failure_count')
                    ->orderByDesc('last_failure')
                    ->limit($limit)
                    ->get()
                    ->toArray();
    }

    // ✅ Get session summary untuk specific session
    public static function getSessionSummary($sessionId): ?array
    {
        $logs = static::where('session_id', $sessionId)->get();
        
        if ($logs->isEmpty()) {
            return null;
        }
        
        $summary = static::getSessionStats($sessionId);
        
        // Add detailed breakdown
        $summary['details'] = [
            'by_status' => $logs->groupBy('status')->map->count()->toArray(),
            'by_method' => $logs->groupBy('method_used')->map->count()->toArray(),
            'by_operation' => $logs->groupBy('operation_type')->map->count()->toArray(),
            'stock_changes' => [
                'positive' => $logs->where('change', '>', 0)->count(),
                'negative' => $logs->where('change', '<', 0)->count(),
                'zero' => $logs->where('change', '=', 0)->count(),
                'total_change' => $logs->sum('change'),
                'average_change' => $logs->where('change', '!=', null)->avg('change')
            ],
            'sample_logs' => $logs->take(5)->map(function($log) {
                return [
                    'sku' => $log->sku,
                    'status' => $log->status,
                    'method' => $log->method_used,
                    'change' => $log->change,
                    'message' => substr($log->message, 0, 100) . (strlen($log->message) > 100 ? '...' : ''),
                    'created_at' => $log->created_at
                ];
            })->toArray()
        ];
        
        return $summary;
    }
}