<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VoucherUsage extends Model
{
    use HasUuids;

    protected $table = 'voucher_usage';

    protected $fillable = [
        'voucher_id', 'customer_id', 'customer_email', 'order_id',
        'discount_amount', 'order_total', 'used_at'
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'order_total' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    // Relationships
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}