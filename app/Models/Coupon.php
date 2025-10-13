<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
        'applicable_categories',
        'applicable_products',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_categories' => 'array',
        'applicable_products' => 'array',
    ];

    // =====================================
    // RELATIONSHIPS
    // =====================================

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'coupon_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coupon_categories');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_products');
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where('is_active', true)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('starts_at')
                          ->orWhere('starts_at', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', $now);
                    })
                    ->where(function ($q) {
                        $q->whereNull('usage_limit')
                          ->orWhereRaw('used_count < usage_limit');
                    });
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', strtoupper($code));
    }

    public function scopeExpiring($query, $days = 7)
    {
        return $query->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // =====================================
    // VALIDATION METHODS
    // =====================================

    /**
     * Check if coupon is currently valid
     */
    public function isValid(): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        $now = now();
        
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if coupon can be applied to a cart
     */
    public function canBeAppliedToCart($cartItems, $cartTotal): array
    {
        // First check basic validity
        if (!$this->isValid()) {
            return [
                'valid' => false,
                'message' => $this->getInvalidReason()
            ];
        }

        // Check minimum amount
        if ($this->minimum_amount && $cartTotal < $this->minimum_amount) {
            return [
                'valid' => false,
                'message' => 'Minimum order amount of Rp ' . number_format($this->minimum_amount, 0, ',', '.') . ' required'
            ];
        }

        // Check if applicable to cart items
        if (!$this->isApplicableToCartItems($cartItems)) {
            return [
                'valid' => false,
                'message' => 'This coupon is not applicable to the items in your cart'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Coupon can be applied'
        ];
    }

    /**
     * Check if coupon applies to cart items
     */
    public function isApplicableToCartItems($cartItems): bool
    {
        // If no specific categories or products are set, applies to all
        if (empty($this->applicable_categories) && empty($this->applicable_products)) {
            return true;
        }

        foreach ($cartItems as $item) {
            if ($this->isApplicableToItem($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if coupon applies to a specific cart item
     */
    public function isApplicableToItem($cartItem): bool
    {
        // If no restrictions, applies to all
        if (empty($this->applicable_categories) && empty($this->applicable_products)) {
            return true;
        }

        $product = $cartItem['product'] ?? null;
        if (!$product) {
            return false;
        }

        // Check specific products
        if (!empty($this->applicable_products) && in_array($product->id, $this->applicable_products)) {
            return true;
        }

        // Check categories
        if (!empty($this->applicable_categories)) {
            $productCategories = $product->categories->pluck('id')->toArray();
            if (array_intersect($this->applicable_categories, $productCategories)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get reason why coupon is invalid
     */
    public function getInvalidReason(): string
    {
        if (!$this->is_active) {
            return 'This coupon is not active';
        }

        $now = now();
        
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return 'This coupon is not yet active. Valid from ' . $this->starts_at->format('M j, Y');
        }
        
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return 'This coupon has expired on ' . $this->expires_at->format('M j, Y');
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return 'This coupon has reached its usage limit';
        }

        return 'This coupon is not valid';
    }

    // =====================================
    // DISCOUNT CALCULATION METHODS
    // =====================================

    /**
     * Calculate discount amount for a cart
     */
    public function calculateDiscount($cartItems, $cartSubtotal, $shippingCost = 0): array
    {
        $discountAmount = 0;
        $freeShipping = false;
        $applicableAmount = $this->getApplicableAmount($cartItems);

        switch ($this->type) {
            case 'percentage':
                $discountAmount = ($applicableAmount * $this->value) / 100;
                
                // Apply maximum discount limit if set
                if ($this->maximum_discount && $discountAmount > $this->maximum_discount) {
                    $discountAmount = $this->maximum_discount;
                }
                break;

            case 'fixed_amount':
                $discountAmount = min($this->value, $applicableAmount);
                break;

            case 'free_shipping':
                $freeShipping = true;
                $discountAmount = $shippingCost;
                break;
        }

        // Ensure discount doesn't exceed cart total
        $maxDiscount = $this->type === 'free_shipping' ? $shippingCost : $cartSubtotal;
        $discountAmount = min($discountAmount, $maxDiscount);

        return [
            'discount_amount' => $discountAmount,
            'free_shipping' => $freeShipping,
            'applicable_amount' => $applicableAmount,
            'original_discount' => $this->type === 'percentage' ? 
                ($applicableAmount * $this->value) / 100 : $this->value,
            'capped_by_maximum' => $this->maximum_discount && 
                $this->type === 'percentage' && 
                ($applicableAmount * $this->value) / 100 > $this->maximum_discount
        ];
    }

    /**
     * Get the total amount this coupon applies to
     */
    private function getApplicableAmount($cartItems): float
    {
        // If no restrictions, applies to entire cart
        if (empty($this->applicable_categories) && empty($this->applicable_products)) {
            return collect($cartItems)->sum('subtotal');
        }

        $applicableAmount = 0;
        
        foreach ($cartItems as $item) {
            if ($this->isApplicableToItem($item)) {
                $applicableAmount += $item['subtotal'] ?? 0;
            }
        }

        return $applicableAmount;
    }

    // =====================================
    // USAGE TRACKING
    // =====================================

    /**
     * Increment usage count when coupon is used
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Decrement usage count if order is cancelled
     */
    public function decrementUsage(): void
    {
        if ($this->used_count > 0) {
            $this->decrement('used_count');
        }
    }

    /**
     * Check if coupon has remaining uses
     */
    public function hasRemainingUses(): bool
    {
        if (!$this->usage_limit) {
            return true;
        }

        return $this->used_count < $this->usage_limit;
    }

    /**
     * Get remaining uses count
     */
    public function getRemainingUses(): ?int
    {
        if (!$this->usage_limit) {
            return null; // Unlimited
        }

        return max(0, $this->usage_limit - $this->used_count);
    }

    // =====================================
    // DISPLAY METHODS
    // =====================================

    /**
     * Get formatted discount value for display
     */
    public function getFormattedValueAttribute(): string
    {
        return match($this->type) {
            'percentage' => $this->value . '%',
            'fixed_amount' => 'Rp ' . number_format($this->value, 0, ',', '.'),
            'free_shipping' => 'Free Shipping',
            default => (string) $this->value
        };
    }

    /**
     * Get coupon status for display
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return 'scheduled';
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return 'expired';
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return 'used_up';
        }

        return 'active';
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'scheduled' => 'Scheduled',
            'expired' => 'Expired',
            'used_up' => 'Used Up',
            'inactive' => 'Inactive',
            default => 'Unknown'
        };
    }

    /**
     * Get coupon summary for display
     */
    public function getSummary(): string
    {
        $summary = '';

        switch ($this->type) {
            case 'percentage':
                $summary = "Get {$this->value}% off";
                if ($this->maximum_discount) {
                    $summary .= " (max Rp " . number_format($this->maximum_discount, 0, ',', '.') . ")";
                }
                break;

            case 'fixed_amount':
                $summary = "Get Rp " . number_format($this->value, 0, ',', '.') . " off";
                break;

            case 'free_shipping':
                $summary = "Free shipping";
                break;
        }

        if ($this->minimum_amount) {
            $summary .= " on orders over Rp " . number_format($this->minimum_amount, 0, ',', '.');
        }

        return $summary;
    }

    // =====================================
    // STATIC HELPER METHODS
    // =====================================

    /**
     * Find and validate a coupon by code
     */
    public static function findByCode(string $code): ?self
    {
        return static::byCode($code)->first();
    }

    /**
     * Validate and apply coupon to cart
     */
    public static function validateAndApply(string $code, $cartItems, $cartSubtotal, $shippingCost = 0): array
    {
        $coupon = static::findByCode($code);

        if (!$coupon) {
            return [
                'success' => false,
                'message' => 'Invalid coupon code',
                'coupon' => null
            ];
        }

        $validation = $coupon->canBeAppliedToCart($cartItems, $cartSubtotal);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'coupon' => $coupon
            ];
        }

        $discount = $coupon->calculateDiscount($cartItems, $cartSubtotal, $shippingCost);

        return [
            'success' => true,
            'message' => 'Coupon applied successfully',
            'coupon' => $coupon,
            'discount' => $discount
        ];
    }

    /**
     * Get available coupon types
     */
    public static function getTypes(): array
    {
        return [
            'percentage' => 'Percentage Off',
            'fixed_amount' => 'Fixed Amount Off',
            'free_shipping' => 'Free Shipping'
        ];
    }

    // =====================================
    // MUTATORS
    // =====================================

    /**
     * Ensure coupon code is always uppercase
     */
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }

    /**
     * Ensure value is positive for percentage and fixed amount
     */
    public function setValueAttribute($value)
    {
        if (in_array($this->type, ['percentage', 'fixed_amount'])) {
            $this->attributes['value'] = max(0, $value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    // =====================================
    // ACCESSORS
    // =====================================

    /**
     * Get formatted minimum amount
     */
    public function getFormattedMinimumAmountAttribute(): ?string
    {
        if (!$this->minimum_amount) {
            return null;
        }

        return 'Rp ' . number_format($this->minimum_amount, 0, ',', '.');
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Check if coupon is expiring soon (within 7 days)
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isBetween(now(), now()->addDays(7));
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentageAttribute(): ?float
    {
        if (!$this->usage_limit) {
            return null;
        }

        return min(100, ($this->used_count / $this->usage_limit) * 100);
    }

    // =====================================
    // ADMIN HELPER METHODS
    // =====================================

    /**
     * Get statistics for admin dashboard
     */
    public function getStatsAttribute(): array
    {
        return [
            'total_discount_given' => $this->orders()->sum('discount_amount'),
            'total_orders' => $this->orders()->count(),
            'average_discount' => $this->orders()->avg('discount_amount') ?? 0,
            'revenue_impact' => $this->orders()->sum('total_amount'),
            'usage_rate' => $this->usage_limit ? 
                round(($this->used_count / $this->usage_limit) * 100, 2) : null
        ];
    }

    /**
     * Generate unique coupon code
     */
    public static function generateUniqueCode($prefix = '', $length = 8): string
    {
        do {
            $code = $prefix . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create bulk coupons
     */
    public static function createBulk(array $data, int $count): array
    {
        $coupons = [];
        
        for ($i = 0; $i < $count; $i++) {
            $couponData = $data;
            $couponData['code'] = static::generateUniqueCode($data['code_prefix'] ?? '', 8);
            $couponData['name'] = ($data['name'] ?? 'Bulk Coupon') . ' #' . ($i + 1);
            
            unset($couponData['code_prefix']);
            
            $coupons[] = static::create($couponData);
        }
        
        return $coupons;
    }

    // =====================================
    // ANALYTICS METHODS
    // =====================================

    /**
     * Get coupon performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $orders = $this->orders();
        
        return [
            'redemption_rate' => $this->usage_limit ? 
                ($this->used_count / $this->usage_limit) * 100 : null,
            'total_savings_provided' => $orders->sum('discount_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'total_revenue_generated' => $orders->sum('total_amount'),
            'conversion_rate' => $this->used_count > 0 ? 
                ($orders->count() / $this->used_count) * 100 : 0,
            'most_popular_day' => $orders->selectRaw('DAYNAME(created_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->orderBy('count', 'desc')
                ->first()?->day,
            'usage_trend' => $orders->selectRaw('DATE(created_at) as date, COUNT(*) as usage')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
        ];
    }

    /**
     * Get top customers who used this coupon
     */
    public function getTopCustomers($limit = 10): array
    {
        return $this->orders()
            ->with('user')
            ->selectRaw('user_id, COUNT(*) as usage_count, SUM(total_amount) as total_spent')
            ->groupBy('user_id')
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'user' => $order->user,
                    'usage_count' => $order->usage_count,
                    'total_spent' => $order->total_spent,
                    'average_order' => $order->total_spent / $order->usage_count
                ];
            })->toArray();
    }
}