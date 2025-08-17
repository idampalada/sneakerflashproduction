<?php
// File: app/Models/CouponUsage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'order_id',
        'discount_amount',
        'used_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    // =====================================
    // RELATIONSHIPS
    // =====================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCoupon($query, $couponId)
    {
        return $query->where('coupon_id', $couponId);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('used_at', now()->year)
                    ->whereMonth('used_at', now()->month);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('used_at', now()->year);
    }

    // =====================================
    // STATIC METHODS
    // =====================================

    /**
     * Record coupon usage
     */
    public static function recordUsage($userId, $couponId, $orderId, $discountAmount)
    {
        return static::create([
            'user_id' => $userId,
            'coupon_id' => $couponId,
            'order_id' => $orderId,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);
    }

    /**
     * Get usage statistics for a user
     */
    public static function getUserStats($userId)
    {
        return [
            'total_coupons_used' => static::byUser($userId)->count(),
            'total_savings' => static::byUser($userId)->sum('discount_amount'),
            'this_month_usage' => static::byUser($userId)->thisMonth()->count(),
            'this_month_savings' => static::byUser($userId)->thisMonth()->sum('discount_amount'),
            'this_year_usage' => static::byUser($userId)->thisYear()->count(),
            'this_year_savings' => static::byUser($userId)->thisYear()->sum('discount_amount'),
        ];
    }

    /**
     * Get usage statistics for a coupon
     */
    public static function getCouponStats($couponId)
    {
        return [
            'total_usage' => static::byCoupon($couponId)->count(),
            'total_discount_given' => static::byCoupon($couponId)->sum('discount_amount'),
            'unique_users' => static::byCoupon($couponId)->distinct('user_id')->count(),
            'this_month_usage' => static::byCoupon($couponId)->thisMonth()->count(),
            'this_month_discount' => static::byCoupon($couponId)->thisMonth()->sum('discount_amount'),
        ];
    }
}