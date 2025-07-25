<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'shipping_address',
        'billing_address',
        'payment_method',
        'payment_status',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function couponUsage()
    {
        return $this->hasOne(CouponUsage::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Auto calculate totals
    public function calculateTotals()
    {
        $subtotal = $this->orderItems()->sum('total_price');
        
        // Calculate tax (11% PPN Indonesia)
        $tax = $subtotal * 0.11;
        
        // Calculate shipping (bisa diambil dari settings)
        $shipping = $this->calculateShipping($subtotal);
        
        // Calculate total
        $total = $subtotal + $tax + $shipping - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'shipping_amount' => $shipping,
            'total_amount' => $total,
        ]);
    }

    private function calculateShipping($subtotal = null)
    {
        // Simple shipping calculation
        $subtotal = $subtotal ?? $this->subtotal;
        
        if ($subtotal >= 500000) { // Free shipping di atas 500rb
            return 0;
        }
        
        // Default shipping fee
        return 15000;
    }

    // Auto generate order number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });

        static::updated(function ($order) {
            // Auto update timestamps for status changes
            if ($order->isDirty('status')) {
                switch ($order->status) {
                    case 'shipped':
                        $order->shipped_at = now();
                        break;
                    case 'delivered':
                        $order->delivered_at = now();
                        break;
                }
                $order->saveQuietly(); // Prevent infinite loop
                
                // Trigger status change notifications
                // event(new OrderStatusChanged($order));
            }

            // Auto update stock when order is confirmed
            if ($order->isDirty('status') && $order->status === 'processing') {
                $order->updateProductStock();
            }
        });
    }

    private static function generateOrderNumber()
    {
        $prefix = 'SF';
        $date = now()->format('Ymd');
        
        // Get last order number for today
        $lastOrder = static::whereDate('created_at', today())
                          ->where('order_number', 'like', "{$prefix}-{$date}-%")
                          ->orderBy('order_number', 'desc')
                          ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $newNumber);
    }

    private function updateProductStock()
    {
        foreach ($this->orderItems as $item) {
            $product = $item->product;
            $product->decrement('stock_quantity', $item->quantity);
        }
    }
}