<?php
// File: app/Models/Category.php - ENHANCED VERSION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order',
        'meta_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    // AUTO GENERATE SLUG
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->getOriginal('slug'))) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
        });
    }

    // GENERATE UNIQUE SLUG
    public static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts()
    {
        return $this->hasMany(Product::class)
                    ->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function featuredProducts()
    {
        return $this->activeProducts()->where('is_featured', true);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public function scopeWithProductCount($query)
    {
        return $query->withCount(['products', 'activeProducts']);
    }

    // Accessors
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }

    public function getActiveProductsCountAttribute()
    {
        return $this->activeProducts()->count();
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        
        // Default category image
        return asset('images/default-category.jpg');
    }

    // Helper Methods
    public function hasProducts()
    {
        return $this->products()->exists();
    }

    public function hasActiveProducts()
    {
        return $this->activeProducts()->exists();
    }

    public function canBeDeleted()
    {
        return !$this->hasProducts();
    }

    // Get route for frontend
    public function getRouteAttribute()
    {
        return route('categories.show', $this->slug);
    }

    // Get URL attribute for easy access
    public function getUrlAttribute()
    {
        return $this->route;
    }
}