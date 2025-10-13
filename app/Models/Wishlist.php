<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\Wishlist
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Wishlist forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder|Wishlist withProduct()
 */
class Wishlist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'product_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =====================================
    // RELATIONSHIPS
    // =====================================

    /**
     * Get the user that owns the wishlist item
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, \App\Models\Wishlist>
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product associated with the wishlist item
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Product, \App\Models\Wishlist>
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // =====================================
    // SCOPES
    // =====================================

    /**
     * Scope a query to only include wishlists for a specific user
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to include only wishlists with active products
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithProduct($query)
    {
        return $query->with(['product' => function($query) {
            $query->where('is_active', true)
                  ->whereNotNull('published_at')
                  ->where('published_at', '<=', now());
        }]);
    }

    /**
     * Scope a query to include only wishlists with products in stock
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithAvailableProducts($query)
    {
        return $query->whereHas('product', function($query) {
            $query->where('is_active', true)
                  ->where('stock_quantity', '>', 0)
                  ->whereNotNull('published_at')
                  ->where('published_at', '<=', now());
        });
    }

    // =====================================
    // STATIC METHODS
    // =====================================

    /**
     * Check if a product is in user's wishlist
     *
     * @param int $userId
     * @param int $productId
     * @return bool
     */
    public static function isInWishlist(int $userId, int $productId): bool
    {
        return static::where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->exists();
    }

    /**
     * Toggle product in user's wishlist
     *
     * @param int $userId
     * @param int $productId
     * @return bool True if added, false if removed
     */
    public static function toggle(int $userId, int $productId): bool
    {
        $wishlist = static::where('user_id', $userId)
                          ->where('product_id', $productId)
                          ->first();

        if ($wishlist) {
            $wishlist->delete();
            return false; // Removed from wishlist
        } else {
            static::create([
                'user_id' => $userId,
                'product_id' => $productId,
            ]);
            return true; // Added to wishlist
        }
    }

    /**
     * Get wishlist count for a specific user
     *
     * @param int $userId
     * @return int
     */
    public static function getCountForUser(int $userId): int
    {
        return static::where('user_id', $userId)->count();
    }

    /**
     * Get all product IDs in user's wishlist
     *
     * @param int $userId
     * @return array<int>
     */
    public static function getProductIdsForUser(int $userId): array
    {
        return static::where('user_id', $userId)
                    ->pluck('product_id')
                    ->toArray();
    }

    /**
     * Remove all wishlist items for a user
     *
     * @param int $userId
     * @return int Number of deleted records
     */
    public static function clearForUser(int $userId): int
    {
        return static::where('user_id', $userId)->delete();
    }

    /**
     * Get popular products (most wishlisted)
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPopularProducts(int $limit = 10)
    {
        return static::select('product_id', DB::raw('COUNT(*) as wishlist_count'))
                    ->with('product')
                    ->groupBy('product_id')
                    ->orderBy('wishlist_count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    // =====================================
    // INSTANCE METHODS
    // =====================================

    /**
     * Check if this wishlist item's product is available
     *
     * @return bool
     */
    public function isProductAvailable(): bool
    {
        return $this->product && 
               $this->product->is_active && 
               $this->product->stock_quantity > 0 &&
               $this->product->published_at &&
               $this->product->published_at <= now();
    }

    /**
     * Get the age of this wishlist item in days
     *
     * @return int
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }
}