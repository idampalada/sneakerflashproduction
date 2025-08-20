<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        'sku_parent',
        'category_id',
        'brand',
        'images',
        'features',
        'specifications',
        'is_active',
        'is_featured',
        'is_featured_sale',
        'stock_quantity',
        'min_stock_level',
        'weight',
        'length',
        'width',
        'height',
        'dimensions',
        'published_at',
        'meta_data',
        'gender_target',
        'product_type',
        'search_keywords',
        'sale_start_date',
        'sale_end_date',
        'available_sizes',
        'available_colors',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_featured_sale' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'published_at' => 'datetime',
        'sale_start_date' => 'date',
        'sale_end_date' => 'date',
        'images' => 'array',
        'features' => 'array',
        'specifications' => 'array',
        'dimensions' => 'array',
        'meta_data' => 'array',
        'gender_target' => 'array',
        'search_keywords' => 'array',
        'available_sizes' => 'array',
        'available_colors' => 'array',
        'meta_keywords' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
        'is_featured_sale' => false,
        'stock_quantity' => 0,
        'min_stock_level' => 5,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->getOriginal('slug'))) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });

        // 🔥 FIX: Add logging for images changes
        static::saving(function ($product) {
            if ($product->isDirty('images')) {
                Log::info('Product: Images being updated', [
                    'product_id' => $product->id ?? 'new',
                    'old_images' => $product->getOriginal('images'),
                    'new_images' => $product->images
                ]);
            }
        });
        
        static::saved(function ($product) {
            $savedImages = $product->fresh()->images ?? [];
            Log::info('Product: Images after save', [
                'product_id' => $product->id,
                'images_count' => count($savedImages),
                'images' => $savedImages
            ]);
        });
    }

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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // 🔥 CRITICAL FIX: Custom mutator untuk images agar tidak hilang saat save
    public function setImagesAttribute($value): void
{
    // 🔥 CRITICAL: Get existing images FIRST
    $existingImages = [];
    if ($this->exists && $this->id) {
        $existingImages = $this->getOriginal('images');
        if (is_string($existingImages)) {
            $decoded = json_decode($existingImages, true);
            $existingImages = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($existingImages)) {
            $existingImages = [];
        }
    }

    Log::info('🔥 Product: MUTATOR CALLED', [
        'product_id' => $this->id ?? 'new',
        'existing_images_count' => count($existingImages),
        'existing_images' => $existingImages,
        'new_value_type' => gettype($value),
        'new_value' => $value
    ]);

    // 🚨 DECISION LOGIC: PRESERVE vs UPDATE
    if (is_null($value) || (is_array($value) && empty($value))) {
        // JIKA VALUE NULL/EMPTY
        if (!empty($existingImages) && $this->exists) {
            // DAN ADA EXISTING IMAGES -> PRESERVE!
            Log::warning('🛡️ Product: Preserving existing images (new value empty)', [
                'product_id' => $this->id,
                'preserving_count' => count($existingImages)
            ]);
            $this->attributes['images'] = json_encode($existingImages);
            return;
        } else {
            // TIDAK ADA EXISTING -> SET EMPTY
            Log::info('📝 Product: Setting empty images (no existing)', [
                'product_id' => $this->id ?? 'new'
            ]);
            $this->attributes['images'] = json_encode([]);
            return;
        }
    }

    // JIKA VALUE ADA -> PROCESS
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $value = $decoded;
        } else {
            $value = !empty($value) ? [$value] : [];
        }
    }

    if (is_array($value)) {
        $cleanImages = array_filter($value, function($image) {
            return !empty($image) && is_string($image);
        });
        $cleanImages = array_values($cleanImages);
        
        if (empty($cleanImages) && !empty($existingImages) && $this->exists) {
            // JIKA CLEAN RESULT EMPTY TAPI ADA EXISTING -> PRESERVE!
            Log::warning('🛡️ Product: Preserving existing (cleaned result empty)', [
                'product_id' => $this->id,
                'preserving_count' => count($existingImages)
            ]);
            $this->attributes['images'] = json_encode($existingImages);
        } else {
            // GUNAKAN CLEAN RESULT
            Log::info('✅ Product: Using new images', [
                'product_id' => $this->id ?? 'new',
                'new_images_count' => count($cleanImages),
                'new_images' => $cleanImages
            ]);
            $this->attributes['images'] = json_encode($cleanImages);
        }
    } else {
        // INVALID TYPE -> PRESERVE EXISTING JIKA ADA
        if (!empty($existingImages) && $this->exists) {
            Log::warning('🛡️ Product: Preserving existing (invalid type)', [
                'product_id' => $this->id,
                'invalid_type' => gettype($value)
            ]);
            $this->attributes['images'] = json_encode($existingImages);
        } else {
            Log::warning('📝 Product: Setting empty (invalid type, no existing)', [
                'product_id' => $this->id ?? 'new'
            ]);
            $this->attributes['images'] = json_encode([]);
        }
    }
}

    // ⭐ FIXED: Safe accessors for array attributes
    public function getImagesAttribute($value)
    {
        if (is_null($value) || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Filter out empty values
                $filtered = array_filter($decoded, function($image) {
                    return !empty($image) && is_string($image);
                });
                
                return array_values($filtered);
            }
        }

        if (is_array($value)) {
            // Filter out empty values
            $filtered = array_filter($value, function($image) {
                return !empty($image) && is_string($image);
            });
            
            return array_values($filtered);
        }

        return [];
    }

    public function getAvailableSizesAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            // Handle JSON string
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // Handle single string value
            return [$value];
        }

        return is_array($value) ? $value : [];
    }

    public function getAvailableColorsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return [$value];
        }

        return is_array($value) ? $value : [];
    }

    public function getFeaturesAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    public function getGenderTargetAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    public function getSearchKeywordsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
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

    public function scopeFeaturedSale($query)
    {
        return $query->where('is_featured_sale', true);
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price')->whereRaw('sale_price < price');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeForGender($query, string $gender)
    {
        if (!in_array($gender, ['mens', 'womens', 'unisex'])) {
            return $query->whereRaw('1 = 0');
        }
        
        return $query->whereRaw("gender_target ? ?", [$gender]);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('product_type', $type);
    }

    public function scopeRunning($query)
    {
        return $query->where('product_type', 'running');
    }

    public function scopeBasketball($query)
    {
        return $query->where('product_type', 'basketball');
    }

    public function scopeTennis($query)
    {
        return $query->where('product_type', 'tennis');
    }

    public function scopeBadminton($query)
    {
        return $query->where('product_type', 'badminton');
    }

    public function scopeLifestyle($query)
    {
        return $query->where('product_type', 'lifestyle_casual');
    }

    public function scopeApparel($query)
    {
        return $query->where('product_type', 'apparel');
    }

    public function scopeCaps($query)
    {
        return $query->where('product_type', 'caps');
    }

    public function scopeBags($query)
    {
        return $query->where('product_type', 'bags');
    }

    public function scopeAccessories($query)
    {
        return $query->where('product_type', 'accessories');
    }

    public function scopeWithSkuParent($query, string $skuParent)
    {
        return $query->where('sku_parent', $skuParent);
    }

    public function scopeHasSkuParent($query)
    {
        return $query->whereNotNull('sku_parent');
    }

    public function scopeWithDimensions($query)
    {
        return $query->whereNotNull('length')
                    ->whereNotNull('width')
                    ->whereNotNull('height');
    }

    // Accessors
    public function getFormattedDimensionsAttribute(): string
    {
        if ($this->length && $this->width && $this->height) {
            return "{$this->length} × {$this->width} × {$this->height} cm";
        }
        return 'Not specified';
    }

    public function getVolumeAttribute(): ?float
    {
        if ($this->length && $this->width && $this->height) {
            return $this->length * $this->width * $this->height;
        }
        return null;
    }

    public function getIsSaleActiveAttribute(): bool
    {
        if (!$this->sale_price) {
            return false;
        }

        $now = now()->toDateString();
        
        if (!$this->sale_start_date && !$this->sale_end_date) {
            return true;
        }
        
        $afterStart = !$this->sale_start_date || $now >= $this->sale_start_date->toDateString();
        $beforeEnd = !$this->sale_end_date || $now <= $this->sale_end_date->toDateString();
        
        return $afterStart && $beforeEnd;
    }

    public function getSizeVariantsAttribute()
    {
        if (!$this->sku_parent) {
            return collect([]);
        }

        return static::where('sku_parent', $this->sku_parent)
                    ->where('id', '!=', $this->id)
                    ->get(['id', 'name', 'sku', 'available_sizes', 'stock_quantity']);
    }

    public function getTotalSkuParentStockAttribute(): int
    {
        if (!$this->sku_parent) {
            return $this->stock_quantity;
        }

        return static::where('sku_parent', $this->sku_parent)->sum('stock_quantity');
    }

    public function getProductTypeLabelAttribute(): string
    {
        return match($this->product_type) {
            'running' => '🏃 Running',
            'basketball' => '🏀 Basketball',
            'tennis' => '🎾 Tennis',
            'badminton' => '🏸 Badminton',
            'lifestyle_casual' => '🚶 Lifestyle/Casual',
            'sneakers' => '👟 Sneakers',
            'training' => '💪 Training',
            'formal' => '👔 Formal',
            'sandals' => '🩴 Sandals',
            'boots' => '🥾 Boots',
            'apparel' => '👕 Apparel',
            'caps' => '🧢 Caps & Hats',
            'bags' => '👜 Bags',
            'accessories' => '🎒 Accessories',
            default => ucfirst(str_replace('_', ' ', $this->product_type ?? 'Unknown'))
        };
    }

    public function getIsFootwearAttribute(): bool
    {
        return in_array($this->product_type, [
            'running',
            'basketball',
            'tennis',
            'badminton',
            'lifestyle_casual',
            'sneakers',
            'training',
            'formal',
            'sandals',
            'boots'
        ]);
    }

    public function getIsApparelAttribute(): bool
    {
        return $this->product_type === 'apparel';
    }

    public function getIsAccessoryAttribute(): bool
    {
        return in_array($this->product_type, [
            'caps',
            'bags',
            'accessories'
        ]);
    }

    // ⭐ FIXED: Safe image methods with improved error handling
    private function generateStorageUrl(string $path): string
    {
        try {
            // Pastikan path tidak dimulai dengan slash
            $cleanPath = ltrim($path, '/');
            
            // Generate URL menggunakan asset() untuk storage link
            return asset('storage/' . $cleanPath);
        } catch (\Exception $e) {
            Log::warning('Product: Error generating storage URL', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return asset('images/default-product.png');
        }
    }

    private function fileExistsInStorage(string $path): bool
    {
        try {
            return Storage::disk('public')->exists($path);
        } catch (\Exception $e) {
            Log::warning('Product: Storage check failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function resolveImagePath(?string $imagePath): string
    {
        if (!$imagePath) {
            return asset('images/default-product.png');
        }

        // Case 1: Full URL (http/https) - return as is
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        // Case 2: URL yang starts dengan http tapi mungkin tidak valid menurut filter_var
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        // Case 3: Relative path - resolve to storage
        if (str_starts_with($imagePath, '/storage/')) {
            return config('app.url') . $imagePath;
        }

        if (str_starts_with($imagePath, 'assets/') || str_starts_with($imagePath, 'images/')) {
            return asset($imagePath);
        }

        if (str_starts_with($imagePath, 'public/')) {
            $imagePath = substr($imagePath, 7);
        }

        if (str_starts_with($imagePath, 'products/')) {
            if ($this->fileExistsInStorage($imagePath)) {
                return $this->generateStorageUrl($imagePath);
            }
        }

        if (!str_contains($imagePath, '/')) {
            $fullPath = 'products/' . $imagePath;
            if ($this->fileExistsInStorage($fullPath)) {
                return $this->generateStorageUrl($fullPath);
            }
        }

        if ($this->fileExistsInStorage($imagePath)) {
            return $this->generateStorageUrl($imagePath);
        }

        $pathWithoutExt = pathinfo($imagePath, PATHINFO_FILENAME);
        $directory = pathinfo($imagePath, PATHINFO_DIRNAME);
        $directory = $directory === '.' ? 'products' : $directory;
        
        $commonExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        foreach ($commonExtensions as $ext) {
            $testPath = $directory . '/' . $pathWithoutExt . '.' . $ext;
            if ($this->fileExistsInStorage($testPath)) {
                return $this->generateStorageUrl($testPath);
            }
        }

        return asset('images/default-product.png');
    }

    public function getFeaturedImageAttribute(): string
    {
        $images = $this->images;
        
        if (!is_array($images) || empty($images)) {
            return asset('images/default-product.png');
        }
        
        return $this->resolveImagePath($images[0]);
    }

    public function getFeaturedImageUrlAttribute(): string
    {
        return $this->getFeaturedImageAttribute();
    }

    public function getImageUrlsAttribute(): array
    {
        $images = $this->images;
        
        if (!is_array($images) || empty($images)) {
            return [];
        }
        
        $urls = [];
        foreach ($images as $imagePath) {
            $resolvedUrl = $this->resolveImagePath($imagePath);
            if ($resolvedUrl && $resolvedUrl !== asset('images/default-product.png')) {
                $urls[] = $resolvedUrl;
            }
        }
        
        return $urls;
    }

    // Helper methods
    public function getDiscountPercentageAttribute()
    {
        if ($this->sale_price && $this->price > 0) {
            return round((($this->price - $this->sale_price) / $this->price) * 100);
        }
        return 0;
    }

    public function getIsOnSaleAttribute()
    {
        return $this->sale_price && $this->sale_price < $this->price;
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getFormattedSalePriceAttribute()
    {
        if ($this->sale_price) {
            return 'Rp ' . number_format($this->sale_price, 0, ',', '.');
        }
        return null;
    }

    // ⭐ FIXED: Safe gender checking
    public function isForGender(string $gender): bool
    {
        if (!in_array($gender, ['mens', 'womens', 'unisex'])) {
            return false;
        }
        
        $genderTarget = $this->gender_target;
        return is_array($genderTarget) && in_array($gender, $genderTarget);
    }

    public function getGenderLabelsAttribute(): array
    {
        $genderTarget = $this->gender_target;
        if (!is_array($genderTarget)) return [];
        
        $labels = [];
        foreach ($genderTarget as $gender) {
            if (in_array($gender, ['mens', 'womens', 'unisex'])) {
                $labels[] = match($gender) {
                    'mens' => "Men's",
                    'womens' => "Women's",
                    'unisex' => 'Unisex',
                    default => $gender
                };
            }
        }
        return $labels;
    }

    public function getGenderBadgesAttribute(): string
    {
        $genderTarget = $this->gender_target;
        if (!is_array($genderTarget)) return '';
        
        $badges = [];
        foreach ($genderTarget as $gender) {
            if (in_array($gender, ['mens', 'womens', 'unisex'])) {
                $badges[] = match($gender) {
                    'mens' => '👨 Men\'s',
                    'womens' => '👩 Women\'s',
                    'unisex' => '🌐 Unisex',
                    default => $gender
                };
            }
        }
        return implode(', ', $badges);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        } elseif ($this->stock_quantity < 10) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    public function getStockStatusLabelAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
            default => 'Unknown'
        };
    }

    // Static methods
    public static function getForMenu(string $menuType)
    {
        if (!in_array($menuType, ['mens', 'womens', 'unisex'])) {
            return static::active()->inStock()->with('category')->whereRaw('1 = 0');
        }
        
        return static::active()
                    ->inStock()
                    ->forGender($menuType)
                    ->with('category')
                    ->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    public static function getFeaturedProducts()
    {
        return static::active()
                    ->featured()
                    ->inStock()
                    ->with('category')
                    ->limit(8)
                    ->get();
    }

    public static function getLatestProducts()
    {
        return static::active()
                    ->inStock()
                    ->with('category')
                    ->latest('created_at')
                    ->limit(12)
                    ->get();
    }

    public static function getSaleProducts()
    {
        return static::active()
                    ->onSale()
                    ->inStock()
                    ->with('category')
                    ->get();
    }

    public static function getFeaturedSaleProducts()
    {
        return static::active()
                    ->featuredSale()
                    ->onSale()
                    ->inStock()
                    ->with('category')
                    ->get();
    }

    public static function getByType(string $type)
    {
        return static::active()
                    ->inStock()
                    ->ofType($type)
                    ->with('category')
                    ->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    public static function getFootwearProducts()
    {
        return static::active()
                    ->inStock()
                    ->whereIn('product_type', [
                        'running', 'basketball', 'tennis', 'badminton',
                        'lifestyle_casual', 'sneakers', 'training',
                        'formal', 'sandals', 'boots'
                    ])
                    ->with('category')
                    ->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    public static function getApparelProducts()
    {
        return static::active()
                    ->inStock()
                    ->apparel()
                    ->with('category')
                    ->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    public static function getAccessoriesProducts()
    {
        return static::active()
                    ->inStock()
                    ->whereIn('product_type', ['caps', 'bags', 'accessories'])
                    ->with('category')
                    ->orderBy('is_featured', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
              ->orWhere('description', 'ilike', "%{$search}%")
              ->orWhere('short_description', 'ilike', "%{$search}%")
              ->orWhere('brand', 'ilike', "%{$search}%")
              ->orWhere('sku', 'ilike', "%{$search}%")
              ->orWhere('sku_parent', 'ilike', "%{$search}%")
              ->orWhereHas('category', function ($cat) use ($search) {
                  $cat->where('name', 'ilike', "%{$search}%");
              });
        });
    }

    // ⭐ FIXED: Safe size and color checking
    public function hasSize(string $size): bool
    {
        $availableSizes = $this->available_sizes;
        return is_array($availableSizes) && in_array($size, $availableSizes);
    }

    public function hasColor(string $color): bool
    {
        $availableColors = $this->available_colors;
        if (!is_array($availableColors)) {
            return false;
        }
        
        return in_array(strtolower($color), array_map('strtolower', $availableColors));
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->features;
        return is_array($features) && in_array($feature, $features);
    }

    public function getMetaTitleAttribute($value)
    {
        return $value ?: $this->name . ' - SneakerFlash';
    }

    public function getMetaDescriptionAttribute($value)
    {
        return $value ?: Str::limit(strip_tags($this->description ?: $this->short_description), 160);
    }

    public function getIsCompleteAttribute(): bool
    {
        $genderTarget = $this->gender_target;
        $hasValidGender = is_array($genderTarget) && 
                         count(array_intersect($genderTarget, ['mens', 'womens', 'unisex'])) > 0;

        return !empty($this->name) &&
               !empty($this->brand) &&
               !empty($this->sku) &&
               !empty($this->sku_parent) &&
               $this->price > 0 &&
               $hasValidGender &&
               !empty($this->product_type);
    }

    public function scopeComplete($query)
    {
        return $query->whereNotNull('name')
                    ->whereNotNull('brand')
                    ->whereNotNull('sku')
                    ->whereNotNull('sku_parent')
                    ->where('price', '>', 0)
                    ->whereNotNull('gender_target')
                    ->whereNotNull('product_type')
                    ->where(function ($q) {
                        $q->whereRaw('gender_target ? ?', ['mens'])
                          ->orWhereRaw('gender_target ? ?', ['womens'])
                          ->orWhereRaw('gender_target ? ?', ['unisex']);
                    });
    }

    public function scopeIncomplete($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('name')
              ->orWhereNull('brand')
              ->orWhereNull('sku')
              ->orWhereNull('sku_parent')
              ->orWhere('price', '<=', 0)
              ->orWhereNull('gender_target')
              ->orWhereNull('product_type')
              ->orWhere(function ($subQ) {
                  $subQ->whereRaw('NOT (gender_target ? ? OR gender_target ? ? OR gender_target ? ?)', 
                                 ['mens', 'womens', 'unisex']);
              });
        });
    }

    public function getOptimizedImagesAttribute(): array
    {
        $images = $this->image_urls;
        
        if (empty($images)) {
            return [
                'primary' => asset('images/default-product.png'),
                'secondary' => [],
                'thumbnails' => [],
                'total_count' => 0,
                'loading_strategy' => 'immediate'
            ];
        }

        return [
            'primary' => $images[0] ?? asset('images/default-product.png'),
            'secondary' => array_slice($images, 1, 2),
            'lazy_load' => array_slice($images, 3),
            'thumbnails' => array_map(function($url) {
                return str_replace('/products/', '/products/thumbs/', $url);
            }, $images),
            'total_count' => count($images),
            'loading_strategy' => count($images) > 3 ? 'progressive' : 'immediate'
        ];
    }

    public function getImageLoadingDataAttribute(): array
    {
        return [
            'product_id' => $this->id,
            'product_name' => $this->name,
            'images' => $this->optimized_images,
            'fallback_image' => asset('images/default-product.png')
        ];
    }

    // 🔥 ADDITIONAL FIX: Validation helper untuk images
    public function validateImageUrls(): array
    {
        $images = $this->images ?? [];
        $validImages = [];
        $invalidImages = [];
        
        foreach ($images as $index => $imagePath) {
            if (empty($imagePath)) {
                $invalidImages[] = "Image $index: Empty path";
                continue;
            }
            
            $resolvedUrl = $this->resolveImagePath($imagePath);
            if ($resolvedUrl === asset('images/default-product.png')) {
                $invalidImages[] = "Image $index: File not found - $imagePath";
            } else {
                $validImages[] = $resolvedUrl;
            }
        }
        
        return [
            'valid_images' => $validImages,
            'invalid_images' => $invalidImages,
            'total_count' => count($images),
            'valid_count' => count($validImages),
            'invalid_count' => count($invalidImages)
        ];
    }

    public function getImagePriorityAttribute(): string
    {
        if ($this->is_featured || $this->is_featured_sale) {
            return 'high';
        }
        
        if ($this->stock_quantity > 0) {
            return 'normal';
        }
        
        return 'low';
    }

    public function getGenderDebugInfoAttribute(): array
    {
        $genderTarget = $this->gender_target;
        
        return [
            'raw_gender_target' => $this->attributes['gender_target'] ?? null,
            'cast_gender_target' => $genderTarget,
            'is_valid_gender' => is_array($genderTarget) && 
                               count(array_intersect($genderTarget, ['mens', 'womens', 'unisex'])) > 0,
            'valid_genders_found' => is_array($genderTarget) ? 
                                   array_intersect($genderTarget, ['mens', 'womens', 'unisex']) : [],
            'invalid_genders_found' => is_array($genderTarget) ? 
                                     array_diff($genderTarget, ['mens', 'womens', 'unisex']) : []
        ];
    }

    public function cleanGenderData(): bool
    {
        $genderTarget = $this->gender_target;
        if (!is_array($genderTarget)) {
            return false;
        }

        $validGenders = ['mens', 'womens', 'unisex'];
        $cleanedGenders = array_intersect($genderTarget, $validGenders);
        
        if (count($cleanedGenders) !== count($genderTarget)) {
            $this->gender_target = array_values($cleanedGenders);
            return $this->save();
        }
        
        return true;
    }

    public static function countByGender(): array
    {
        $counts = [
            'mens' => static::whereRaw('gender_target ? ?', ['mens'])->count(),
            'womens' => static::whereRaw('gender_target ? ?', ['womens'])->count(),
            'unisex' => static::whereRaw('gender_target ? ?', ['unisex'])->count(),
            'empty_gender' => static::whereNull('gender_target')->count(),
            'total' => static::count()
        ];
        
        return $counts;
    }

    // ⭐ NEW: Safe array conversion methods
    public function ensureArrayAttribute($attribute): array
    {
        $value = $this->attributes[$attribute] ?? null;
        
        if (is_null($value)) {
            return [];
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($value) ? $value : [];
    }

    public function toArraySafe(): array
    {
        $array = $this->toArray();
        
        // Ensure array fields are properly formatted
        $arrayFields = [
            'images', 'features', 'specifications', 'dimensions',
            'gender_target', 'search_keywords', 'available_sizes',
            'available_colors', 'meta_keywords', 'meta_data'
        ];
        
        foreach ($arrayFields as $field) {
            if (isset($array[$field])) {
                $array[$field] = $this->ensureArrayAttribute($field);
            }
        }
        
        return $array;
    }
}