<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GineeSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        // Kolom yang WAJIB ada di tabel (dari migration asli)
        'type',                    // enum: product_pull, stock_push, webhook, manual
        'status',                  // enum: started, completed, failed, cancelled
        'items_processed',
        'items_successful',
        'items_failed',
        'items_skipped',
        'parameters',
        'summary',
        'errors',
        'error_message',
        'started_at',
        'completed_at',
        'duration_seconds',
        'triggered_by',
        'batch_id',
        
        // Kolom yang ditambahkan untuk individual tracking
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
        'session_id',
    ];

    protected $casts = [
        'parameters' => 'array',
        'summary' => 'array',
        'errors' => 'array',
        'ginee_response' => 'array',
        'dry_run' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'sku', 'sku');
    }

    // Scopes
    public function scopeSync($query)
    {
        return $query->where('operation_type', 'sync');
    }

    public function scopePush($query)
    {
        return $query->where('operation_type', 'push');
    }

    public function scopeSuccess($query)
    {
        return $query->whereIn('status', ['success', 'completed']);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'error']);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // Accessors
    public function getStockChangeAttribute(): int
    {
        return ($this->new_stock ?? 0) - ($this->old_stock ?? 0);
    }

    public function getOperationLabelAttribute(): string
    {
        return match($this->operation_type) {
            'sync' => '📥 Sync from Ginee',
            'push' => '📤 Push to Ginee',
            default => $this->operation_type
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'success', 'completed' => '✅ Success',
            'failed', 'error' => '❌ Failed',
            'skipped' => '⏭️ Skipped',
            'started' => '🔄 Running',
            'cancelled' => '⏹️ Cancelled',
            default => $this->status
        };
    }

    // Static methods for logging
    public static function logSync(array $data): self
    {
        return self::create(array_merge($data, [
            'type' => 'stock_push', // Mapping ke enum yang ada
            'operation_type' => 'sync',
            'session_id' => $data['session_id'] ?? self::generateSessionId(),
        ]));
    }

    public static function logPush(array $data): self
    {
        return self::create(array_merge($data, [
            'type' => 'stock_push', // Mapping ke enum yang ada
            'operation_type' => 'push',
            'session_id' => $data['session_id'] ?? self::generateSessionId(),
        ]));
    }

    public static function generateSessionId(): string
    {
        return 'session_' . now()->format('Ymd_His') . '_' . \Illuminate\Support\Str::random(6);
    }

    // Helper methods
    public function isSuccess(): bool
    {
        return in_array($this->status, ['success', 'completed']);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'error']);
    }

    public function hasStockChange(): bool
    {
        return $this->old_stock !== $this->new_stock;
    }
}