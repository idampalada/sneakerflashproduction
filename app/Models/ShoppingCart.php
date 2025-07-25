<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{
    use HasFactory;

    protected $table = 'shopping_cart';

    protected $fillable = [
        'session_id',
        'user_id',
        'product_id',
        'quantity',
        'product_options',
    ];

    protected $casts = [
        'product_options' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('is_active', true)->where('stock_quantity', '>', 0);
        });
    }

    // Accessors
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->product->current_price;
    }

    // Auto cleanup and validations
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cartItem) {
            // Validate stock availability
            if ($cartItem->product && $cartItem->quantity > $cartItem->product->stock_quantity) {
                throw new \Exception('Insufficient stock');
            }
        });

        static::updating(function ($cartItem) {
            // Validate stock when updating quantity
            if ($cartItem->isDirty('quantity') && $cartItem->product) {
                if ($cartItem->quantity > $cartItem->product->stock_quantity) {
                    throw new \Exception('Insufficient stock');
                }
            }
        });
    }

    // Helper methods
    public static function getCartTotal($userId = null, $sessionId = null)
    {
        $query = static::query();
        
        if ($userId) {
            $query->forUser($userId);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        }

        return $query->active()->get()->sum('subtotal');
    }

    public static function getCartCount($userId = null, $sessionId = null)
    {
        $query = static::query();
        
        if ($userId) {
            $query->forUser($userId);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        }

        return $query->active()->sum('quantity');
    }

    public static function clearExpiredCarts()
    {
        // Clear carts older than 30 days
        static::where('created_at', '<', now()->subDays(30))->delete();
    }
}