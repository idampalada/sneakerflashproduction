<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VoucherSyncLog extends Model
{
    use HasUuids;

    protected $table = 'voucher_sync_log';

    protected $fillable = [
        'sync_type', 'status', 'records_processed', 'errors_count',
        'error_details', 'synced_at', 'execution_time_ms'
    ];

    protected $casts = [
        'synced_at' => 'datetime',
        'records_processed' => 'integer',
        'errors_count' => 'integer',
        'execution_time_ms' => 'integer',
    ];
}