<?php
// File: app/Models/Product.php - PostgreSQL Optimized

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'sale_price',
        'sku',
        'category_id',
        'brand',
        'images',
        'features',
        'specifications',
        'is_active',
        'is_featured',
        'stock_quantity',
        'min_stock_level',
        'weight',
        'dimensions',
        'published_at',
        'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'images' => 'array', // PostgreSQL JSON handling
            'features' => 'array', // PostgreSQL JSON handling
            'specifications' => 'array', // PostgreSQL JSON handling
            'dimensions' => 'array', // PostgreSQL JSON handling
            'meta_data' => 'array', // PostgreSQL JSON handling
            'published_at' => 'datetime',
        ];
    }

    // PostgreSQL specific scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    // PostgreSQL full-text search
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%") // PostgreSQL case-insensitive
              ->orWhere('description', 'ILIKE', "%{$search}%")
              ->orWhere('short_description', 'ILIKE', "%{$search}%")
              ->orWhere('brand', 'ILIKE', "%{$search}%");
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(ShoppingCart::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(Wishlist::class);
    }

    // Accessors
    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?: $this->price;
    }

    public function getIsOnSaleAttribute()
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->is_on_sale) {
            return 0;
        }
        
        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }
}