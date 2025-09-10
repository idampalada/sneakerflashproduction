<?php

// 📁 app/Models/GineeSyncLog.php
// Enhanced model untuk menampilkan data yang benar

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GineeSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'type',
        'status', 
        'operation_type',
        'sku',
        'product_name',
        'old_stock',
        'new_stock',
        'change',
        'message',
        'error_message',
        'dry_run',
        'metadata',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'old_stock' => 'integer',
        'new_stock' => 'integer', 
        'change' => 'integer',
        'dry_run' => 'boolean',
        'metadata' => 'json',
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
                    if ($this->status === 'success') {
                        return $this->change == 0 ? 'Skipped' : 'Would Update';
                    }
                    return 'Dry Run ' . ucfirst($this->status);
                }
                return ucfirst($this->status);
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
            'skipped' => $logs->where('status', 'success')->where('change', 0)->count(),
            'would_update' => $logs->where('status', 'success')->where('change', '!=', 0)->count(),
            'dry_run' => $logs->where('dry_run', true)->count() > 0,
            'created_at' => $logs->min('created_at'),
            'completed_at' => $logs->max('created_at')
        ];
    }
}