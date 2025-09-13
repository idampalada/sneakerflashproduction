<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // ⭐ CORRECTED: Group by sku_parent, differentiate by SKU
        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category');

        // Apply filters BEFORE grouping
        $this->applyAllFilters($query, $request);
        
        // Get all products
        $allProducts = $query->get();
        
        Log::info('Products found before grouping', [
            'total_products' => $allProducts->count(),
            'sample_data' => $allProducts->take(3)->map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'sku_parent' => $p->sku_parent,
                    'size' => $p->available_sizes
                ];
            })
        ]);
        
        // ⭐ CORRECTED: Group products by sku_parent (same product, different sizes)
        $groupedProducts = $this->groupProductsBySkuParent($allProducts);
        
        Log::info('Products after grouping', [
            'grouped_count' => $groupedProducts->count(),
            'sample_group' => $groupedProducts->first()
        ]);
        
        // Apply sorting to grouped products
        $groupedProducts = $this->applySortingToCollection($groupedProducts, $request);

        // Manual pagination
        $perPage = 12;
        $currentPage = $request->page ?? 1;
        $offset = ($currentPage - 1) * $perPage;
        
        $paginatedProducts = $groupedProducts->slice($offset, $perPage)->values();
        
        // Create pagination info
        $total = $groupedProducts->count();
        $lastPage = ceil($total / $perPage);
        
        // Get filter data
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $brands = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        $stockCounts = $this->getStockCounts();

        // Get size and color options - ⭐ FIXED: Safe handling of array data
        $availableSizes = collect();
        $availableColors = collect();
        
        if (Schema::hasColumn('products', 'available_sizes')) {
            $availableSizes = $allProducts
                ->filter(function($product) {
                    // ⭐ FIXED: Ensure available_sizes is an array or string
                    $sizes = $product->available_sizes;
                    return !empty($sizes);
                })
                ->flatMap(function($product) {
                    $sizes = $product->available_sizes;
                    // ⭐ FIXED: Handle both array and string formats
                    if (is_array($sizes)) {
                        return $sizes;
                    } elseif (is_string($sizes)) {
                        return [$sizes];
                    }
                    return [];
                })
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        if (Schema::hasColumn('products', 'available_colors')) {
            $allProducts->each(function ($product) use (&$availableColors) {
                $colors = $product->available_colors;
                // ⭐ FIXED: Safe array handling
                if (is_array($colors)) {
                    $availableColors = $availableColors->merge($colors);
                } elseif (is_string($colors)) {
                    $availableColors = $availableColors->push($colors);
                }
            });
            $availableColors = $availableColors->filter()->unique()->sort()->values();
        }

        // Get user wishlist product IDs
        $userWishlistProductIds = collect();
        if (Auth::check()) {
            $allProductIds = $paginatedProducts->pluck('id');
            $userWishlistProductIds = Wishlist::where('user_id', Auth::id())
                ->whereIn('product_id', $allProductIds)
                ->pluck('product_id')
                ->toArray();
        }

        return view('frontend.products.index', compact(
            'paginatedProducts',
            'categories', 
            'brands', 
            'stockCounts',
            'availableSizes',
            'availableColors',
            'userWishlistProductIds',
            'total',
            'currentPage',
            'lastPage',
            'perPage'
        ));
    }

    // ⭐ NEW: Product Detail Show Method
    public function show($slug)
    {
        try {
            // Find product by slug
            $product = Product::where('slug', $slug)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->with('category')
                ->first();

            if (!$product) {
                abort(404, 'Product not found');
            }

            // ⭐ ENHANCED: Get size variants for the same product
            $sizeVariants = collect();
            if (!empty($product->sku_parent)) {
                $sizeVariants = Product::where('sku_parent', $product->sku_parent)
                    ->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->where('id', '!=', $product->id)
                    ->get()
                    ->map(function ($variant) {
                        // Extract size from SKU or available_sizes
                        $size = null;
                        if (is_array($variant->available_sizes) && !empty($variant->available_sizes)) {
                            $size = $variant->available_sizes[0];
                        } elseif (is_string($variant->available_sizes)) {
                            $size = $variant->available_sizes;
                        } else {
                            // Extract from SKU pattern
                            $size = $this->extractSizeFromSku($variant->sku, $variant->sku_parent);
                        }
                        
                        return [
                            'id' => $variant->id,
                            'size' => $size ?: 'One Size',
                            'stock' => $variant->stock_quantity ?? 0,
                            'sku' => $variant->sku,
                            'price' => $variant->sale_price ?: $variant->price,
                            'original_price' => $variant->price,
                            'available' => ($variant->stock_quantity ?? 0) > 0,
                            'slug' => $variant->slug
                        ];
                    });
            }

            // Get related products (same category or brand)
            $relatedProducts = Product::where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->where('id', '!=', $product->id)
                ->where(function ($query) use ($product) {
                    if ($product->category_id) {
                        $query->where('category_id', $product->category_id);
                    }
                    if ($product->brand) {
                        $query->orWhere('brand', $product->brand);
                    }
                })
                ->inRandomOrder()
                ->limit(12)
                ->get();

            // ⭐ SAFE: Ensure arrays are properly formatted
            $product = $this->formatProductArrays($product);

            return view('frontend.products.show', compact('product', 'sizeVariants', 'relatedProducts'));

        } catch (\Exception $e) {
            Log::error('Product show error: ' . $e->getMessage(), [
                'slug' => $slug,
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(404, 'Product not found');
        }
    }

    /**
     * ⭐ CORRECTED: Group products by sku_parent (same product), variants by SKU
     */
    private function groupProductsBySkuParent($products)
    {
        // Group by sku_parent (same product, different sizes)
        return $products->groupBy('sku_parent')->map(function ($productGroup, $skuParent) {
            
            // Skip products without sku_parent (treat as individual products)
            if (empty($skuParent)) {
                return $productGroup->map(function ($product) {
                    $enhanced = $product->toArray();
                    $enhanced['size_variants'] = collect([]);
                    $enhanced['total_stock'] = $product->stock_quantity ?? 0;
                    $enhanced['has_multiple_sizes'] = false;
                    return (object) $enhanced;
                });
            }
            
            // Get the first product as representative (main product info)
            $representativeProduct = $productGroup->first();
            
            // ⭐ KEY FIX: Create size variants from all products with same sku_parent but different SKU
            $sizeVariants = $productGroup->map(function ($product) {
                // Extract size from SKU pattern (SBKVN0A3HZFCAR-S -> S)
                $sizeFromSku = $this->extractSizeFromSku($product->sku, $product->sku_parent);
                
                // ⭐ FIXED: Safe size handling
                $productSize = null;
                if (is_array($product->available_sizes) && !empty($product->available_sizes)) {
                    $productSize = $product->available_sizes[0];
                } elseif (is_string($product->available_sizes) && !empty($product->available_sizes)) {
                    $productSize = $product->available_sizes;
                }
                
                return [
                    'id' => $product->id,
                    'size' => $productSize ?: $sizeFromSku ?: 'One Size',
                    'stock' => $product->stock_quantity ?? 0,
                    'sku' => $product->sku,
                    'price' => $product->sale_price ?: $product->price,
                    'original_price' => $product->price,
                    'available' => ($product->stock_quantity ?? 0) > 0
                ];
            })->sortBy('size');
            
            // Calculate total stock from all variants
            $totalStock = $sizeVariants->sum('stock');
            
            // Create enhanced product object (only one per sku_parent group)
            $enhancedProduct = $representativeProduct->toArray();
            $enhancedProduct['size_variants'] = $sizeVariants;
            $enhancedProduct['total_stock'] = $totalStock;
            $enhancedProduct['has_multiple_sizes'] = $sizeVariants->count() > 1;
            
            Log::info('Grouped product', [
                'sku_parent' => $skuParent,
                'variants_count' => $sizeVariants->count(),
                'sizes' => $sizeVariants->pluck('size')->toArray(),
                'total_stock' => $totalStock
            ]);
            
            // Return single enhanced product representing the group
            return collect([(object) $enhancedProduct]);
            
        })->flatten(1)->values(); // Flatten to get single collection of products
    }

    /**
     * ⭐ FIXED: Apply filters with safe array handling
     */
    private function applyAllFilters($query, Request $request)
    {
        if ($request->filled('category')) {
            $category = $request->category;
            
            if (Schema::hasColumn('products', 'gender_target')) {
                switch ($category) {
                    case 'mens':
                        $query->whereRaw("gender_target @> ?", [json_encode(['mens'])]);
                        break;
                    case 'womens':
                        $query->whereRaw("gender_target @> ?", [json_encode(['womens'])]);
                        break;
                    case 'unisex':
                        $query->whereRaw("gender_target @> ?", [json_encode(['unisex'])]);
                        break;
                }
            }
        }

        $typeFilter = $request->type ?? $request->section;
        
        if ($typeFilter && Schema::hasColumn('products', 'product_type')) {
            $footwearTypes = [
                'lifestyle_casual', 'running', 'training', 'basketball', 
                'tennis', 'badminton', 'sneakers', 'formal', 'sandals', 'boots'
            ];
            
            $apparelTypes = ['apparel'];
            $accessoryTypes = ['caps', 'bags', 'accessories'];
            
            switch ($typeFilter) {
                case 'footwear':
                    $query->whereIn('product_type', $footwearTypes);
                    break;
                case 'apparel':
                    $query->whereIn('product_type', $apparelTypes);
                    break;
                case 'accessories':
                    $query->whereIn('product_type', $accessoryTypes);
                    break;
                case 'lifestyle':
                case 'lifestyle_casual':
                    $query->where('product_type', 'lifestyle_casual');
                    break;
                case 'running':
                    $query->where('product_type', 'running');
                    break;
                case 'training':
                    $query->where('product_type', 'training');
                    break;
                case 'basketball':
                    $query->where('product_type', 'basketball');
                    break;
                case 'tennis':
                    $query->where('product_type', 'tennis');
                    break;
                case 'badminton':
                    $query->where('product_type', 'badminton');
                    break;
                case 'caps':
                    $query->where('product_type', 'caps');
                    break;
                case 'bags':
                    $query->where('product_type', 'bags');
                    break;
                default:
                    $query->where('product_type', $typeFilter);
                    break;
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand')) {
            $brands = $request->brand;
            if (!is_array($brands)) {
                $brands = [$brands];
            }
            $brands = array_filter($brands);
            if (!empty($brands)) {
                $query->whereIn('brand', $brands);
            }
        }

        if ($request->filled('brands')) {
            $brands = $request->brands;
            if (!is_array($brands)) {
                $brands = [$brands];
            }
            $brands = array_filter($brands);
            if (!empty($brands)) {
                $query->whereIn('brand', $brands);
            }
        }

        if ($request->filled('sale') && $request->sale == 'true') {
            $query->whereNotNull('sale_price')->whereRaw('sale_price < price');
        }

        if ($request->filled('featured') && $request->featured == '1') {
            $query->where('is_featured', true);
        }

        if ($request->filled('min_price')) {
            $query->whereRaw('COALESCE(sale_price, price) >= ?', [$request->min_price]);
        }
        if ($request->filled('max_price')) {
            $query->whereRaw('COALESCE(sale_price, price) <= ?', [$request->max_price]);
        }

        if ($request->filled('availability')) {
            $availability = $request->availability;
            if (!is_array($availability)) {
                $availability = [$availability];
            }
            
            $query->where(function ($q) use ($availability) {
                if (in_array('in_stock', $availability)) {
                    $q->orWhere('stock_quantity', '>', 0);
                }
                if (in_array('not_available', $availability)) {
                    $q->orWhere('stock_quantity', '<=', 0);
                }
            });
        }

        // ⭐ FIXED: Safe size filtering
        if ($request->filled('sizes') && Schema::hasColumn('products', 'available_sizes')) {
            $sizes = $request->sizes;
            if (!is_array($sizes)) {
                $sizes = is_string($sizes) ? explode(',', $sizes) : [$sizes];
            }
            $sizes = array_filter($sizes);
            
            if (!empty($sizes)) {
                $query->where(function ($q) use ($sizes) {
                    foreach ($sizes as $size) {
                        $size = trim($size);
                        // Handle both array and string formats
                        $q->orWhere(function($subQ) use ($size) {
                            $subQ->where('available_sizes', $size)
                                 ->orWhereRaw("available_sizes @> ?", [json_encode([$size])]);
                        });
                    }
                });
            }
        }

        if ($request->filled('selected_sizes') && Schema::hasColumn('products', 'available_sizes')) {
            $selectedSizes = $request->selected_sizes;
            if (is_string($selectedSizes)) {
                $selectedSizes = explode(',', $selectedSizes);
            }
            $selectedSizes = array_filter(array_map('trim', $selectedSizes));
            
            if (!empty($selectedSizes)) {
                $query->where(function ($q) use ($selectedSizes) {
                    foreach ($selectedSizes as $size) {
                        // Handle both array and string formats
                        $q->orWhere(function($subQ) use ($size) {
                            $subQ->where('available_sizes', $size)
                                 ->orWhereRaw("available_sizes @> ?", [json_encode([$size])]);
                        });
                    }
                });
            }
        }

        // ⭐ FIXED: Safe color filtering
        if ($request->filled('color') && Schema::hasColumn('products', 'available_colors')) {
            $color = trim($request->color);
            $query->where(function($q) use ($color) {
                $q->where('available_colors', $color)
                  ->orWhereRaw("available_colors @> ?", [json_encode([$color])]);
            });
        }

        if (($request->filled('colors') || $request->filled('selected_colors')) && Schema::hasColumn('products', 'available_colors')) {
            $colors = $request->colors ?? $request->selected_colors;
            
            if (!is_array($colors)) {
                $colors = is_string($colors) ? explode(',', $colors) : [$colors];
            }
            $colors = array_filter(array_map('trim', $colors));
            
            if (!empty($colors)) {
                $query->where(function ($q) use ($colors) {
                    foreach ($colors as $color) {
                        $q->orWhere(function($subQ) use ($color) {
                            $subQ->where('available_colors', $color)
                                 ->orWhereRaw("available_colors @> ?", [json_encode([$color])]);
                        });
                    }
                });
            }
        }

        if ($request->filled('conditions') && Schema::hasColumn('products', 'features')) {
            $conditions = $request->conditions;
            if (!is_array($conditions)) {
                $conditions = [$conditions];
            }
            
            $query->where(function ($q) use ($conditions) {
                foreach ($conditions as $condition) {
                    $conditionMapping = [
                        'express_shipping' => 'Express Shipping',
                        'brand_new' => 'Brand New',
                        'used' => 'Used',
                        'pre_order' => 'Pre-Order'
                    ];
                    
                    $feature = $conditionMapping[$condition] ?? $condition;
                    $q->orWhereRaw("features @> ?", [json_encode([$feature])]);
                }
            });
        }
    }

    /**
     * ⭐ NEW: Extract size from SKU pattern
     */
    private function extractSizeFromSku($sku, $skuParent)
    {
        // Pattern: SBKVN0A3HZFCAR-S -> extract "S"
        // Pattern: SBKVN0A3HZFCAR-M -> extract "M"
        
        if (empty($sku) || empty($skuParent)) {
            return null;
        }
        
        // Find the size part after the last dash
        $parts = explode('-', $sku);
        if (count($parts) >= 2) {
            $sizePart = end($parts);
            
            // Common size patterns
            if (in_array(strtoupper($sizePart), ['S', 'M', 'L', 'XL', 'XXL', 'XS'])) {
                return strtoupper($sizePart);
            }
            
            // Shoe sizes (numeric with optional .5)
            if (is_numeric($sizePart) || preg_match('/^\d+\.?5?$/', $sizePart)) {
                return $sizePart;
            }
        }
        
        return null;
    }

    /**
     * ⭐ SAFE: Format product arrays to ensure they're proper arrays
     */
    private function formatProductArrays($product)
    {
        // Ensure images is an array
        if (!is_array($product->images)) {
            $product->images = $product->images ? [$product->images] : [];
        }
        
        // Ensure available_sizes is an array
        if (!is_array($product->available_sizes)) {
            if (is_string($product->available_sizes) && !empty($product->available_sizes)) {
                $product->available_sizes = [$product->available_sizes];
            } else {
                $product->available_sizes = [];
            }
        }
        
        // Ensure available_colors is an array
        if (!is_array($product->available_colors)) {
            if (is_string($product->available_colors) && !empty($product->available_colors)) {
                $product->available_colors = [$product->available_colors];
            } else {
                $product->available_colors = [];
            }
        }
        
        // Ensure features is an array
        if (!is_array($product->features)) {
            if (is_string($product->features) && !empty($product->features)) {
                $product->features = [$product->features];
            } else {
                $product->features = [];
            }
        }
        
        // Ensure gender_target is an array
        if (!is_array($product->gender_target)) {
            if (is_string($product->gender_target) && !empty($product->gender_target)) {
                $product->gender_target = [$product->gender_target];
            } else {
                $product->gender_target = [];
            }
        }
        
        return $product;
    }

    /**
     * ⭐ NEW: Apply sorting to collection
     */
    private function applySortingToCollection($products, $request)
    {
        $sortBy = $request->sort ?? 'latest';
        
        switch ($sortBy) {
            case 'price_low':
                return $products->sortBy(function ($product) {
                    return $product->sale_price ?: $product->price;
                });
            case 'price_high':  
                return $products->sortByDesc(function ($product) {
                    return $product->sale_price ?: $product->price;
                });
            case 'name':
            case 'name_az':
                return $products->sortBy('name');
            case 'name_za':
                return $products->sortByDesc('name');
            case 'featured':
                return $products->sortByDesc('is_featured')->sortByDesc('created_at');
            case 'latest':
            default:
                return $products->sortByDesc('created_at');
        }
    }

    // Keep all other existing methods unchanged...
    private function applySorting($query, Request $request)
    {
        $sortBy = $request->sort ?? 'latest';
        
        switch ($sortBy) {
            case 'price_low':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':  
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name':
            case 'name_az':
                $query->orderBy('name', 'asc');
                break;
            case 'name_za':
                $query->orderBy('name', 'desc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')
                      ->orderBy('created_at', 'desc');
                break;
            case 'relevance':
                if ($request->filled('search') || $request->filled('q')) {
                    $search = $request->search ?? $request->q;
                    $query->orderByRaw("
                        CASE 
                            WHEN name ILIKE ? THEN 1
                            WHEN brand ILIKE ? THEN 2  
                            WHEN description ILIKE ? THEN 3
                            ELSE 4
                        END
                    ", ["%{$search}%", "%{$search}%", "%{$search}%"]);
                } else {
                    $query->orderBy('created_at', 'desc');
                }
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

    private function getStockCounts()
    {
        $inStock = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('stock_quantity', '>', 0)
            ->count();

        $outOfStock = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('stock_quantity', '<=', 0)
            ->count();

        return [
            'in_stock' => $inStock,
            'not_available' => $outOfStock
        ];
    }

    // Category-specific product pages
    public function mens(Request $request)
    {
        $request->merge(['category' => 'mens']);
        return $this->index($request);
    }

    public function womens(Request $request)
    {
        $request->merge(['category' => 'womens']);
        return $this->index($request);
    }

    public function unisex(Request $request)
    {
        $request->merge(['category' => 'unisex']);
        return $this->index($request);
    }

    public function sale(Request $request)
    {
        $request->merge(['sale' => 'true']);
        return $this->index($request);
    }

    public function accessories(Request $request)
    {
        $request->merge(['type' => 'accessories']);
        return $this->index($request);
    }

    public function brand(Request $request)
    {
        // Handle brand-specific filtering
        return $this->index($request);
    }

    public function search(Request $request)
    {
        $searchQuery = $request->q ?? $request->search;
        
        if (empty($searchQuery)) {
            return redirect()->route('products.index');
        }

        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category')
            ->where(function ($q) use ($searchQuery) {
                $q->where('name', 'ilike', "%{$searchQuery}%")
                  ->orWhere('description', 'ilike', "%{$searchQuery}%")
                  ->orWhere('short_description', 'ilike', "%{$searchQuery}%")
                  ->orWhere('brand', 'ilike', "%{$searchQuery}%")
                  ->orWhere('sku', 'ilike', "%{$searchQuery}%")
                  ->orWhereHas('category', function ($cat) use ($searchQuery) {
                      $cat->where('name', 'ilike', "%{$searchQuery}%");
                  });
            });

        $products = $query->paginate(12);
        
        return view('frontend.products.search', compact('products', 'searchQuery'));
    }

    public function filter(Request $request)
    {
        return $this->index($request);
    }

    // API Methods
    public function quickSearch(Request $request)
    {
        $query = $request->q;
        
        if (empty($query)) {
            return response()->json([]);
        }

        $products = Product::where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('brand', 'ilike', "%{$query}%");
            })
            ->limit(5)
            ->get(['id', 'name', 'slug', 'price', 'sale_price', 'images']);

        return response()->json($products);
    }

    public function getVariants($productId)
    {
        $product = Product::find($productId);
        
        if (!$product || !$product->sku_parent) {
            return response()->json([]);
        }

        $variants = Product::where('sku_parent', $product->sku_parent)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get(['id', 'name', 'sku', 'available_sizes', 'stock_quantity', 'price', 'sale_price']);

        return response()->json($variants);
    }

    public function checkStock($productId)
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'stock_quantity' => $product->stock_quantity ?? 0,
            'is_available' => ($product->stock_quantity ?? 0) > 0
        ]);
    }
}