<?php
// File: app/Models/Product.php - UPDATED VERSION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
            'images' => 'array',
            'features' => 'array',
            'specifications' => 'array',
            'dimensions' => 'array',
            'meta_data' => 'array',
            'published_at' => 'datetime',
        ];
    }

    // AUTO GENERATE SKU JIKA KOSONG
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Auto-generate SKU if not provided
            if (empty($product->sku)) {
                $product->sku = static::generateSKU($product);
            }

            // Auto-generate slug if not provided
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }

            // Set default published_at if not provided
            if (empty($product->published_at)) {
                $product->published_at = now();
            }
        });

        static::updating(function ($product) {
            // Update slug if name changed
            if ($product->isDirty('name') && empty($product->getOriginal('slug'))) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    // GENERATE SKU OTOMATIS
    public static function generateSKU($product)
    {
        // Format: BRAND-CATEGORY-RANDOM (e.g., NIKE-RUN-1234)
        $brand = $product->brand ? strtoupper(substr($product->brand, 0, 4)) : 'PROD';
        
        $category = 'GEN';
        if ($product->category_id) {
            $categoryModel = \App\Models\Category::find($product->category_id);
            if ($categoryModel) {
                $category = strtoupper(substr($categoryModel->name, 0, 3));
            }
        }

        $random = strtoupper(Str::random(4));
        $sku = "{$brand}-{$category}-{$random}";

        // Ensure uniqueness
        $counter = 1;
        $originalSku = $sku;
        while (static::where('sku', $sku)->exists()) {
            $sku = $originalSku . '-' . $counter;
            $counter++;
        }

        return $sku;
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

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', 'ILIKE', "%{$brand}%");
    }

    // PostgreSQL full-text search
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('description', 'ILIKE', "%{$search}%")
              ->orWhere('short_description', 'ILIKE', "%{$search}%")
              ->orWhere('brand', 'ILIKE', "%{$search}%")
              ->orWhere('sku', 'ILIKE', "%{$search}%");
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

    public function getMainImageAttribute()
    {
        return $this->images && count($this->images) > 0 ? $this->images[0] : null;
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getFormattedSalePriceAttribute()
    {
        return $this->sale_price ? 'Rp ' . number_format($this->sale_price, 0, ',', '.') : null;
    }

    public function getStockStatusAttribute()
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        } elseif ($this->stock_quantity <= $this->min_stock_level) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    public function getStockStatusLabelAttribute()
    {
        return match($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
            default => 'Unknown'
        };
    }

    // Helper Methods
    public function isAvailable()
    {
        return $this->is_active && 
               $this->stock_quantity > 0 && 
               $this->published_at && 
               $this->published_at <= now();
    }

    public function canBePurchased($quantity = 1)
    {
        return $this->isAvailable() && $this->stock_quantity >= $quantity;
    }

    public function decrementStock($quantity)
    {
        if ($this->stock_quantity >= $quantity) {
            $this->decrement('stock_quantity', $quantity);
            return true;
        }
        return false;
    }

    public function incrementStock($quantity)
    {
        $this->increment('stock_quantity', $quantity);
    }
}