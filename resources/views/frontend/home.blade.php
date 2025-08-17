@extends('layouts.app')

@section('title', 'SneakerFlash - Premium Sneakers for Everyone')

@section('content')

    <!-- ⭐ SAFE: Best Sellers Section with Grouping -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Best Sellers</h2>
                <p class="text-gray-600 text-lg">Most popular products from our customers</p>
            </div>

            @php
                // ⭐ SAFE: Get products without complex joins first
                try {
                    $bestSellersQuery = \App\Models\Product::where('is_active', true)
                        ->where('stock_quantity', '>', 0)
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now())
                        ->orderByDesc('is_featured')
                        ->orderByDesc('created_at')
                        ->get();

                    // ⭐ GROUP BY SKU_PARENT to remove duplicates
                    $bestSellers = $bestSellersQuery->groupBy('sku_parent')->map(function ($productGroup, $skuParent) {
                        if (empty($skuParent)) {
                            return $productGroup->first(); // Individual products
                        }
                        
                        // For grouped products, return the representative
                        $representative = $productGroup->first();
                        
                        // Clean product name
                        if (!empty($skuParent)) {
                            $cleanName = preg_replace('/\s*-\s*' . preg_quote($skuParent, '/') . '\s*/', '', $representative->name);
                            $cleanName = preg_replace('/\s*-\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanName);
                            $cleanName = preg_replace('/\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanName);
                            $representative->name = trim($cleanName, ' -');
                        }
                        
                        return $representative;
                    })->values()->take(6);
                } catch (\Exception $e) {
                    $bestSellers = collect(); // Empty collection on error
                }
            @endphp

            @if($bestSellers && $bestSellers->count() > 0)
                <!-- Products Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6">
                    @foreach($bestSellers as $product)
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="relative">
                                <a href="/products/{{ $product->slug ?? '#' }}">
                                    @if(isset($product->images) && is_array($product->images) && count($product->images) > 0)
                                        @php
                                            $firstImage = $product->images[0];
                                            $imageUrl = filter_var($firstImage, FILTER_VALIDATE_URL) 
                                                ? $firstImage 
                                                : asset('storage/' . ltrim($firstImage, '/'));
                                        @endphp
                                        <img src="{{ $imageUrl }}" 
                                             alt="{{ $product->name ?? 'Product' }}"
                                             class="w-full h-40 object-cover"
                                             onerror="this.src='{{ asset('images/default-product.png') }}'">
                                    @else
                                        <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-2xl text-gray-400"></i>
                                        </div>
                                    @endif
                                </a>
                                
                                @if(isset($product->sale_price) && isset($product->price) && $product->sale_price && $product->sale_price < $product->price)
                                    <div class="absolute top-2 left-2">
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                            Sale
                                        </span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 text-sm">
                                    {{ Str::limit($product->name ?? 'Product', 20) }}
                                </h3>
                                <div class="flex items-center justify-between">
                                    @if(isset($product->sale_price) && $product->sale_price && isset($product->price) && $product->sale_price < $product->price)
                                        <span class="text-sm font-bold text-red-600">
                                            Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                                        </span>
                                    @elseif(isset($product->price))
                                        <span class="text-sm font-bold text-gray-900">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback Best Sellers -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6">
                    @for($i = 1; $i <= 6; $i++)
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="relative">
                                <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-2xl text-gray-400"></i>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 text-sm">Best Product {{ $i }}</h3>
                                <span class="text-sm font-bold text-gray-900">
                                    Rp {{ number_format(rand(400000, 1500000), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endfor
                </div>
            @endif

            <div class="text-center mt-12">
                <a href="/products" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    View All Best Sellers
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Featured Products</h2>
                <p class="text-gray-600 text-lg">Hand-picked favorites from our collection</p>
            </div>
            
            @if(isset($featuredProducts) && $featuredProducts->count() > 0)
                <!-- Real Products from Database -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    @foreach($featuredProducts as $product)
                        <div class="product-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="relative">
                                <a href="/products/{{ $product->slug ?? '#' }}">
                                    @if(isset($product->images) && is_array($product->images) && count($product->images) > 0)
                                        @php
                                            $firstImage = $product->images[0];
                                            $imageUrl = filter_var($firstImage, FILTER_VALIDATE_URL) 
                                                ? $firstImage 
                                                : asset('storage/' . ltrim($firstImage, '/'));
                                        @endphp
                                        <img src="{{ $imageUrl }}" 
                                             alt="{{ $product->name ?? 'Product' }}"
                                             class="w-full h-64 object-cover"
                                             onerror="this.src='{{ asset('images/default-product.png') }}'">
                                    @else
                                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-4xl text-gray-400"></i>
                                        </div>
                                    @endif
                                </a>
                                
                                @if(isset($product->sale_price) && $product->sale_price && isset($product->price) && $product->sale_price < $product->price)
                                    <div class="absolute top-3 left-3">
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                            -{{ round((($product->price - $product->sale_price) / $product->price) * 100) }}%
                                        </span>
                                    </div>
                                @endif
                                
                                <div class="absolute top-3 right-3">
                                    <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                        Featured
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <div class="mb-2">
                                    @if(isset($product->category) && $product->category)
                                        <span class="text-sm text-gray-500">{{ $product->category->name ?? 'Category' }}</span>
                                    @endif
                                </div>
                                
                                <h3 class="font-semibold text-gray-900 mb-2">{{ $product->name ?? 'Product' }}</h3>
                                
                                @if(isset($product->brand) && $product->brand)
                                    <p class="text-sm text-gray-600 mb-2">{{ $product->brand }}</p>
                                @endif
                                
                                <div class="flex items-center justify-between">
                                    <div>
                                        @if(isset($product->sale_price) && $product->sale_price)
                                            <span class="text-lg font-bold text-red-600">
                                                Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                                            </span>
                                            @if(isset($product->price) && $product->price)
                                                <span class="text-sm text-gray-500 line-through ml-2">
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        @elseif(isset($product->price))
                                            <span class="text-lg font-bold text-gray-900">
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Stock Status -->
                                @php
                                    $stockQuantity = 0;
                                    if (isset($product->total_stock)) {
                                        $stockQuantity = $product->total_stock;
                                    } elseif (isset($product->stock_quantity)) {
                                        $stockQuantity = $product->stock_quantity;
                                    }
                                @endphp
                                
                                @if($stockQuantity > 0)
                                    <p class="text-xs text-green-600 mt-2">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        In stock
                                    </p>
                                @else
                                    <p class="text-xs text-red-600 mt-2">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        Out of stock
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback Products -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    @for($i = 1; $i <= 4; $i++)
                        <div class="product-card bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="relative">
                                <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-4xl text-gray-400"></i>
                                </div>
                                <div class="absolute top-3 right-3">
                                    <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                        Featured
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <div class="mb-2">
                                    <span class="text-sm text-gray-500">Sample Category</span>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Sample Product {{ $i }}</h3>
                                <p class="text-sm text-gray-600 mb-2">Sample Brand</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-bold text-gray-900">
                                        Rp {{ number_format(rand(500000, 2000000), 0, ',', '.') }}
                                    </span>
                                </div>
                                <p class="text-xs text-green-600 mt-2">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    In stock
                                </p>
                            </div>
                        </div>
                    @endfor
                </div>
            @endif

            <div class="text-center mt-12">
                <a href="/products?featured=1" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    View All Featured Products
                </a>
            </div>
        </div>
    </section>

    <!-- Latest Products -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Latest Arrivals</h2>
                <p class="text-gray-600 text-lg">Fresh kicks just dropped</p>
            </div>
            
            @if(isset($latestProducts) && $latestProducts->count() > 0)
                <!-- Real Latest Products from Database -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6">
                    @foreach($latestProducts as $product)
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="relative">
                                <a href="/products/{{ $product->slug ?? '#' }}">
                                    @if(isset($product->images) && is_array($product->images) && count($product->images) > 0)
                                        @php
                                            $firstImage = $product->images[0];
                                            $imageUrl = filter_var($firstImage, FILTER_VALIDATE_URL) 
                                                ? $firstImage 
                                                : asset('storage/' . ltrim($firstImage, '/'));
                                        @endphp
                                        <img src="{{ $imageUrl }}" 
                                             alt="{{ $product->name ?? 'Product' }}"
                                             class="w-full h-40 object-cover"
                                             onerror="this.src='{{ asset('images/default-product.png') }}'">
                                    @else
                                        <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-2xl text-gray-400"></i>
                                        </div>
                                    @endif
                                </a>
                                
                                @if(isset($product->sale_price) && $product->sale_price)
                                    <div class="absolute top-2 left-2">
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                            Sale
                                        </span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 text-sm">
                                    {{ Str::limit($product->name ?? 'Product', 20) }}
                                </h3>
                                <div class="flex items-center justify-between">
                                    @if(isset($product->sale_price) && $product->sale_price)
                                        <span class="text-sm font-bold text-red-600">
                                            Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                                        </span>
                                    @elseif(isset($product->price))
                                        <span class="text-sm font-bold text-gray-900">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback Latest Products -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6">
                    @for($i = 1; $i <= 6; $i++)
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="relative">
                                <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-2xl text-gray-400"></i>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 text-sm">New Product {{ $i }}</h3>
                                <span class="text-sm font-bold text-gray-900">
                                    Rp {{ number_format(rand(400000, 1500000), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endfor
                </div>
            @endif

            <div class="text-center mt-12">
                <a href="/products?sort=latest" class="inline-block bg-gray-800 text-white px-8 py-3 rounded-lg hover:bg-gray-900 transition-colors font-medium">
                    View All New Arrivals
                </a>
            </div>
        </div>
    </section>

@endsection