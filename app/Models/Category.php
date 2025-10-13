<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class Category extends Model
{
    use HasFactory;

    // Core fields yang pasti ada di database
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order',
        'meta_data',
    ];

    // Basic casting untuk field yang pasti ada
    protected $casts = [
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    // Default values
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    // Dynamic fillable - tambahkan field jika ada di database
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // Tambahkan field optional ke fillable jika kolom ada
        $optionalFields = [
            'menu_placement',
            'secondary_menus', 
            'category_keywords',
            'show_in_menu',
            'is_featured',
            'meta_title',
            'meta_description', 
            'meta_keywords',
            'brand_color',
        ];

        foreach ($optionalFields as $field) {
            if (Schema::hasColumn('categories', $field) && !in_array($field, $this->fillable)) {
                $this->fillable[] = $field;
                
                // Tambahkan casting untuk field JSON
                if (in_array($field, ['secondary_menus', 'category_keywords', 'meta_keywords'])) {
                    $this->casts[$field] = 'array';
                }
                
                // Tambahkan casting untuk field boolean
                if (in_array($field, ['show_in_menu', 'is_featured'])) {
                    $this->casts[$field] = 'boolean';
                }
            }
        }
    }

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

    public function scopeInMenu($query)
    {
        // Hanya gunakan jika kolom ada
        if (Schema::hasColumn('categories', 'show_in_menu')) {
            return $query->where('show_in_menu', true);
        }
        return $query;
    }

    public function scopeFeatured($query)
    {
        // Hanya gunakan jika kolom ada
        if (Schema::hasColumn('categories', 'is_featured')) {
            return $query->where('is_featured', true);
        }
        return $query;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    public function scopeWithProductCount($query)
    {
        return $query->withCount(['products', 'activeProducts']);
    }

    public function scopeForMenu($query, string $menuType)
    {
        if (Schema::hasColumn('categories', 'menu_placement')) {
            return $query->where(function ($q) use ($menuType) {
                $q->where('menu_placement', $menuType);
                
                // Jika ada secondary_menus column, cek juga di situ
                if (Schema::hasColumn('categories', 'secondary_menus')) {
                    $q->orWhereJsonContains('secondary_menus', $menuType);
                }
            });
        }
        
        return $query;
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
        return asset('images/default-category.png');
    }

    public function getFullUrlAttribute()
    {
        return url('/categories/' . $this->slug);
    }

    // Helper Methods dengan safety check
    public function isInMenu(string $menuType): bool
    {
        // Cek menu_placement jika ada
        if (Schema::hasColumn('categories', 'menu_placement') && $this->menu_placement === $menuType) {
            return true;
        }

        // Cek secondary_menus jika ada
        if (Schema::hasColumn('categories', 'secondary_menus') && 
            $this->secondary_menus && 
            is_array($this->secondary_menus)) {
            return in_array($menuType, $this->secondary_menus);
        }

        return false;
    }

    public function getMenuBadgeColor(): string
    {
        if (!Schema::hasColumn('categories', 'menu_placement')) {
            return 'gray';
        }

        return match($this->menu_placement ?? 'general') {
            'mens' => 'blue',
            'womens' => 'pink',
            'kids' => 'green',
            'accessories' => 'purple',
            'general' => 'gray',
            default => 'gray'
        };
    }

    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    public function hasActiveProducts(): bool
    {
        return $this->activeProducts()->exists();
    }

    // Safe getter untuk optional fields
    public function getShowInMenu(): bool
    {
        if (Schema::hasColumn('categories', 'show_in_menu')) {
            return $this->show_in_menu ?? true;
        }
        return true;
    }

    public function getIsFeatured(): bool
    {
        if (Schema::hasColumn('categories', 'is_featured')) {
            return $this->is_featured ?? false;
        }
        return false;
    }

    public function getMenuPlacement(): string
    {
        if (Schema::hasColumn('categories', 'menu_placement')) {
            return $this->menu_placement ?? 'general';
        }
        return 'general';
    }

    // Static Helper Methods
    public static function getForMenu(string $menuType)
    {
        return static::active()
                    ->inMenu()
                    ->forMenu($menuType)
                    ->ordered()
                    ->get();
    }

    public static function getFeaturedCategories()
    {
        return static::active()
                    ->featured()
                    ->ordered()
                    ->limit(6)
                    ->get();
    }

    public static function getMenuCategories()
    {
        return static::active()
                    ->inMenu()
                    ->ordered()
                    ->get()
                    ->groupBy(function ($category) {
                        return $category->getMenuPlacement();
                    });
    }
}