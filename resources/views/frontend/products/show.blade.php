{{-- Create: resources/views/frontend/categories/show.blade.php --}}
@extends('layouts.app')

@section('title', (isset($category) ? $category->name : 'Category') . ' - SneakerFlash')

@section('content')
    <!-- Page Header -->
    <section class="bg-gray-100 py-8">
        <div class="container mx-auto px-4">
            <nav class="text-sm mb-4">
                <ol class="flex space-x-2 text-gray-600">
                    <li><a href="/" class="hover:text-blue-600">Home</a></li>
                    <li>/</li>
                    <li><a href="/products" class="hover:text-blue-600">Products</a></li>
                    <li>/</li>
                    <li class="text-gray-900">{{ $category->name ?? 'Category' }}</li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $category->name ?? 'Category' }}</h1>
                    @if(isset($category) && $category->description)
                        <p class="text-gray-600 mt-2">{{ $category->description }}</p>
                    @endif
                </div>
                <div class="text-gray-600">
                    @if(isset($products))
                        {{ $products->total() ?? 0 }} products found
                    @else
                        0 products found
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <aside class="lg:w-1/4">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h3 class="font-semibold text-gray-900 mb-4">Filters</h3>
                    
                    <form method="GET">
                        <!-- Search -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   placeholder="Search in {{ $category->name ?? 'category' }}..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Brands -->
                        @if(isset($brands) && $brands->count() > 0)
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                                <select name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Brands</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand }}" {{ request('brand') == $brand ? 'selected' : '' }}>
                                            {{ $brand }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Price Range -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="min_price" value="{{ request('min_price') }}" 
                                       placeholder="Min" class="px-3 py-2 border border-gray-300 rounded-md">
                                <input type="number" name="max_price" value="{{ request('max_price') }}" 
                                       placeholder="Max" class="px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <button type="submit" class="w-full btn btn-primary">Apply Filters</button>
                            <a href="/categories/{{ $category->slug ?? 'category' }}" class="w-full btn btn-outline">Clear Filters</a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Products Grid -->
            <main class="lg:w-3/4">
                <!-- Sort Options -->
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Sort by:</span>
                        <select name="sort" onchange="updateSort(this.value)" 
                                class="px-3 py-2 border border-gray-300 rounded-md">
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                            <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name A-Z</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                @if(isset($products) && $products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($products as $product)
                            <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                                <div class="relative">
                                    <a href="/products/{{ $product->slug }}">
                                        @if($product->images && count($product->images) > 0)
                                            <img src="{{ Storage::url($product->images[0]) }}" 
                                                 alt="{{ $product->name }}"
                                                 class="w-full h-64 object-cover">
                                        @else
                                            <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-image text-4xl text-gray-400"></i>
                                            </div>
                                        @endif
                                    </a>
                                    
                                    @if($product->sale_price)
                                        <div class="absolute top-3 left-3">
                                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                                -{{ round((($product->price - $product->sale_price) / $product->price) * 100) }}%
                                            </span>
                                        </div>
                                    @endif
                                    
                                    @if($product->is_featured)
                                        <div class="absolute top-3 right-3">
                                            <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                                Featured
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="p-6">
                                    <div class="mb-2">
                                        <span class="text-sm text-gray-500">{{ $product->category->name ?? 'Category' }}</span>
                                        @if($product->brand)
                                            <span class="text-sm text-gray-500"> • {{ $product->brand }}</span>
                                        @endif
                                    </div>
                                    
                                    <h3 class="font-semibold text-gray-900 mb-2">
                                        <a href="/products/{{ $product->slug }}" class="hover:text-blue-600 transition-colors">
                                            {{ $product->name }}
                                        </a>
                                    </h3>
                                    
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-2">
                                            @if($product->sale_price)
                                                <span class="text-lg font-bold text-red-600">
                                                    Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                                                </span>
                                                <span class="text-sm text-gray-500 line-through">
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-lg font-bold text-gray-900">
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <button onclick="addToCart({{ $product->id }})" 
                                                class="flex-1 btn btn-primary text-sm">
                                            <i class="fas fa-cart-plus mr-1"></i>
                                            Add to Cart
                                        </button>
                                        <a href="/products/{{ $product->slug }}" 
                                           class="btn btn-outline text-sm px-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                    
                                    @if($product->stock_quantity <= 5 && $product->stock_quantity > 0)
                                        <p class="text-xs text-orange-600 mt-2">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Only {{ $product->stock_quantity }} left!
                                        </p>
                                    @elseif($product->stock_quantity <= 0)
                                        <p class="text-xs text-red-600 mt-2">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Out of stock
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if(method_exists($products, 'hasPages') && $products->hasPages())
                        <div class="mt-8 flex justify-center">
                            {{ $products->links() }}
                        </div>
                    @endif
                @else
                    <!-- Empty State -->
                    <div class="text-center py-12">
                        <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No products found</h3>
                        <p class="text-gray-500 mb-4">
                            @if(request()->hasAny(['search', 'brand', 'min_price', 'max_price']))
                                Try adjusting your filters or search terms
                            @else
                                This category doesn't have any products yet
                            @endif
                        </p>
                        <div class="space-x-4">
                            @if(request()->hasAny(['search', 'brand', 'min_price', 'max_price']))
                                <a href="/categories/{{ $category->slug ?? 'category' }}" class="btn btn-primary">Clear Filters</a>
                            @endif
                            <a href="/products" class="btn btn-outline">Browse All Products</a>
                        </div>
                    </div>
                @endif
            </main>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function updateSort(value) {
        const url = new URL(window.location);
        url.searchParams.set('sort', value);
        window.location.href = url.toString();
    }
</script>
@endpush