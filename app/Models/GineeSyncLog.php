<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GineeSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'operation_type',
        'sku',
        'product_name',
        'status',
        'old_stock',
        'old_warehouse_stock',
        'new_stock',
        'new_warehouse_stock',
        'message',
        'ginee_response',
        'transaction_id',
        'method_used',
        'initiated_by',
        'dry_run',
        'batch_size',
        'session_id',
    ];

    protected $casts = [
        'ginee_response' => 'array',
        'dry_run' => 'boolean',
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
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
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
            'success' => '✅ Success',
            'failed' => '❌ Failed',
            'skipped' => '⏭️ Skipped',
            default => $this->status
        };
    }

    // Static methods for logging
    public static function logSync(array $data): self
    {
        return self::create(array_merge($data, [
            'operation_type' => 'sync',
            'session_id' => $data['session_id'] ?? self::generateSessionId(),
        ]));
    }

    public static function logPush(array $data): self
    {
        return self::create(array_merge($data, [
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
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function hasStockChange(): bool
    {
        return $this->old_stock !== $this->new_stock;
    }
}