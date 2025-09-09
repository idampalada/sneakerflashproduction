<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GineeProductMapping extends Model
{
    protected $fillable = [
        'product_id',
        'ginee_master_sku',
        'ginee_product_id',
        'ginee_warehouse_id',
        'sync_enabled',
        'stock_sync_enabled',
        'price_sync_enabled',
        'last_product_sync',
        'last_stock_sync',
        'last_price_sync',
        'stock_quantity_ginee',
        'price_ginee',
        'ginee_product_data',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
        'stock_sync_enabled' => 'boolean',
        'price_sync_enabled' => 'boolean',
        'last_product_sync' => 'datetime',
        'last_stock_sync' => 'datetime',
        'last_price_sync' => 'datetime',
        'ginee_product_data' => 'json',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}