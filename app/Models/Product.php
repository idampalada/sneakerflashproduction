<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'price',
        'sale_price',
        'stock_quantity',
        'brand',
        'sizes',
        'colors',
        'images',
        'is_active',
        'is_featured',
        'category_id',
        'weight',
        'specifications',
        'published_at',
    ];

    protected $casts = [
        'sizes' => 'array',
        'colors' => 'array',
        'images' => 'array',
        'specifications' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cartItems()
    {
        return $this->hasMany(ShoppingCart::class);
    }

    public function productImages()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function getCurrentPriceAttribute()
{
    return $this->sale_price ?? $this->price;
}

public function getDiscountPercentageAttribute()
{
    if ($this->sale_price && $this->price > $this->sale_price) {
        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }
    return 0;
}

public function getInStockAttribute()
{
    return $this->stock_quantity > 0;
}

// Scopes
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

public function scopeFeatured($query)
{
    return $query->where('is_featured', true);
}

public function scopePublished($query)
{
    return $query->whereNotNull('published_at')
                 ->where('published_at', '<=', now());
}

public function scopeInStock($query)
{
    return $query->where('stock_quantity', '>', 0);
}

    // Accessors & Mutators
    

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getReviewsCountAttribute()
    {
        return $this->reviews()->count();
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    // Auto generate SKU if empty
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->sku)) {
                $product->sku = 'SF-' . strtoupper(Str::random(8));
            }
        });

        // Update stock status when stock changes
        static::updated(function ($product) {
            if ($product->isDirty('stock_quantity')) {
                if ($product->stock_quantity <= 0) {
                    $product->update(['is_active' => false]);
                }
                
                // Trigger low stock alert
                if ($product->stock_quantity <= 5 && $product->stock_quantity > 0) {
                    // Dispatch low stock notification
                    // event(new LowStockAlert($product));
                }
            }
        });
    }
}