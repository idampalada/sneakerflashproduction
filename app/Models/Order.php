<?php
// File: app/Models/Order.php - PostgreSQL Optimized

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
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
        'payment_token',
        'payment_url',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'notes',
        'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'shipping_address' => 'array', // PostgreSQL JSON
            'billing_address' => 'array', // PostgreSQL JSON
            'meta_data' => 'array', // PostgreSQL JSON
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    // PostgreSQL specific scopes
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

    public function scopeGuest($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeRegistered($query)
    {
        return $query->whereNotNull('user_id');
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Accessors
    public function getIsGuestOrderAttribute()
    {
        return is_null($this->user_id);
    }

    public function getCustomerDisplayNameAttribute()
    {
        return $this->is_guest_order 
            ? $this->customer_name . ' (Guest)'
            : ($this->user ? $this->user->name : 'Unknown Customer');
    }

    public function getContactEmailAttribute()
    {
        return $this->is_guest_order 
            ? $this->customer_email 
            : optional($this->user)->email;
    }
}