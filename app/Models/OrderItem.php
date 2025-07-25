<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'product_name',
        'product_sku',
        'product_options',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'product_options' => 'array',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Auto calculate total price
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($orderItem) {
            // Auto calculate total price
            $orderItem->total_price = $orderItem->quantity * $orderItem->unit_price;
            
            // Store product snapshot
            if ($orderItem->product) {
                $orderItem->product_name = $orderItem->product->name;
                $orderItem->product_sku = $orderItem->product->sku;
            }
        });

        static::updating(function ($orderItem) {
            // Recalculate total if quantity or unit_price changes
            if ($orderItem->isDirty(['quantity', 'unit_price'])) {
                $orderItem->total_price = $orderItem->quantity * $orderItem->unit_price;
            }
        });

        static::saved(function ($orderItem) {
            // Auto update order totals when order item changes
            $orderItem->order->calculateTotals();
        });
    }
}