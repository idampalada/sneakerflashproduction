{{-- resources/views/frontend/products/search.blade.php --}}
@extends('layouts.app')

@section('title', 'Search Results - SneakerFlash')

@section('content')
    <!-- Page Header -->
    <section class="bg-white py-6 border-b border-gray-200">
        <div class="container mx-auto px-4">
            <nav class="text-sm mb-4">
                <ol class="flex space-x-2 text-gray-600">
                    <li><a href="/" class="hover:text-blue-600">Home</a></li>
                    <li>/</li>
                    <li class="text-gray-900">Search Results</li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Search Results</h1>
                    @if(isset($searchQuery) && $searchQuery)
                        <p class="text-gray-600 mt-2">Results for: <span class="font-semibold">"{{ $searchQuery }}"</span></p>
                    @endif
                </div>
                <div class="text-gray-600">
                    {{ $products->total() ?? 0 }} products found
                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 py-8">
        <div class="flex gap-8">
            <!-- Filters Sidebar - Same as products/index.blade.php -->
            <aside class="w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl p-6 sticky top-4 border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-6 text-lg">Refine Search</h3>
                    
                    <form method="GET" id="filterForm">
                        <!-- Keep the search query -->
                        @if(request('q'))
                            <input type="hidden" name="q" value="{{ request('q') }}">
                        @endif

                        <!-- Search within results -->
                        <div class="mb-8">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search within results</label>
                            <input type="text" name="search" placeholder="Refine your search..." 
                                   value="{{ request('search') }}"
                                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Category Filter -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Category</h4>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="category" value="" class="mr-3" 
                                           {{ request('category') == '' ? 'checked' : '' }}>
                                    <span class="text-sm">All Categories</span>
                                </label>
                                @if(isset($categories))
                                    @foreach($categories as $category)
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="category" value="{{ $category->slug }}" class="mr-3"
                                                   {{ request('category') == $category->slug ? 'checked' : '' }}>
                                            <span class="text-sm">{{ $category->name }}</span>
                                        </label>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <!-- Brands Filter -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Brands</h4>
                            <div class="max-h-48 overflow-y-auto space-y-2">
                                @if(isset($brands) && $brands->count() > 0)
                                    @foreach($brands as $brand)
                                        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-1 rounded">
                                            <input type="checkbox" name="brands[]" value="{{ $brand }}" class="mr-3"
                                                   {{ in_array($brand, request('brands', [])) ? 'checked' : '' }}>
                                            <span class="text-sm">{{ $brand }}</span>
                                        </label>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-8">
                            <h4 class="font-semibold text-gray-900 mb-4 text-sm uppercase tracking-wide">Price Range</h4>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" placeholder="Minimum Price" 
                                       value="{{ request('min_price') }}"
                                       class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <input type="number" name="max_price" placeholder="Maximum Price" 
                                       value="{{ request('max_price') }}"
                                       class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="space-y-3">
                            <button type="submit" class="w-full bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition-colors">
                                Apply Filters
                            </button>
                            <a href="{{ route('search') }}{{ request('q') ? '?q=' . urlencode(request('q')) : '' }}" 
                               class="w-full border border-gray-300 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-50 transition-colors block text-center">
                                Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Search Results -->
            <main class="flex-1">
                <!-- Sort Options -->
                <div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-600 font-medium">Sort by:</span>
                            <select name="sort" onchange="updateSort(this.value)" class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="relevance" {{ request('sort') == 'relevance' ? 'selected' : '' }}>Relevance</option>
                                <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                <option value="name_az" {{ request('sort') == 'name_az' ? 'selected' : '' }}>Name A-Z</option>
                                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                                <option value="featured" {{ request('sort') == 'featured' ? 'selected' : '' }}>Featured</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <button id="gridView" class="p-2 rounded-lg border border-gray-200 text-blue-600 bg-blue-50">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button id="listView" class="p-2 rounded-lg border border-gray-200 text-gray-400">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Grid -->
                <div id="productsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @if(isset($products) && $products->count() > 0)
                        @foreach($products as $product)
                            <div class="product-card bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-lg transition-all duration-300 group">
                                <div class="relative aspect-square bg-gray-50 overflow-hidden">
                                    @if($product->images && count($product->images) > 0)
                                        <img src="{{ $product->featured_image }}" 
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                            <i class="fas fa-shoe-prints text-4xl text-gray-300"></i>
                                        </div>
                                    @endif
                                    
                                    <!-- Product badges -->
                                    <div class="absolute top-3 left-3 flex flex-col gap-2">
                                        @if($product->is_featured)
                                            <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                Featured
                                            </span>
                                        @endif
                                        @if($product->sale_price)
                                            @php
                                                $discount = round((($product->price - $product->sale_price) / $product->price) * 100);
                                            @endphp
                                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                -{{ $discount }}%
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Wishlist button -->
                                    <button class="absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:shadow-lg transition-shadow">
                                        <i class="far fa-heart text-gray-400 hover:text-red-500 transition-colors"></i>
                                    </button>
                                </div>
                                
                                <div class="p-4">
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-500 uppercase tracking-wide">
                                            {{ $product->category->name ?? 'Sneakers' }} • {{ $product->brand ?? 'Brand' }}
                                        </span>
                                    </div>
                                    
                                    <h3 class="font-semibold text-gray-900 mb-3 text-sm leading-tight">
                                        <a href="{{ route('products.show', $product->slug) }}" 
                                           class="hover:text-blue-600 transition-colors">
                                            {!! highlightSearchTerm($product->name, request('q')) !!}
                                        </a>
                                    </h3>
                                    
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-2">
                                            @if($product->sale_price)
                                                <span class="text-lg font-bold text-red-600">
                                                    Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                                                </span>
                                                <span class="text-sm text-gray-400 line-through">
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-lg font-bold text-gray-900">
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-2">
                                        <button class="flex-1 bg-gray-900 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
                                            <i class="fas fa-shopping-cart mr-1"></i>
                                            Add to Cart
                                        </button>
                                        <button class="px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <i class="fas fa-eye text-gray-600"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Empty state -->
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No results found</h3>
                            <p class="text-gray-500 mb-4">
                                @if(request('q'))
                                    We couldn't find any products matching "<strong>{{ request('q') }}</strong>"
                                @else
                                    Try adjusting your search terms or filters
                                @endif
                            </p>
                            <a href="{{ route('products.index') }}" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Browse All Products
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Pagination -->
                @if(isset($products) && method_exists($products, 'links'))
                    <div class="mt-8">
                        {{ $products->appends(request()->query())->links() }}
                    </div>
                @endif
            </main>
        </div>
    </div>

    <script>
        // View toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('gridView').addEventListener('click', function() {
                document.getElementById('productsContainer').classList.remove('list-view');
                this.classList.add('text-blue-600', 'bg-blue-50');
                document.getElementById('listView').classList.remove('text-blue-600', 'bg-blue-50');
                document.getElementById('listView').classList.add('text-gray-400');
            });

            document.getElementById('listView').addEventListener('click', function() {
                document.getElementById('productsContainer').classList.add('list-view');
                this.classList.add('text-blue-600', 'bg-blue-50');
                document.getElementById('gridView').classList.remove('text-blue-600', 'bg-blue-50');
                document.getElementById('gridView').classList.add('text-gray-400');
            });
        });

        function updateSort(value) {
            const url = new URL(window.location);
            url.searchParams.set('sort', value);
            window.location = url;
        }
    </script>
@endsection

@php
// Helper function to highlight search terms
if (!function_exists('highlightSearchTerm')) {
    function highlightSearchTerm($text, $searchTerm) {
        if (!$searchTerm) return $text;
        
        return preg_replace(
            '/(' . preg_quote($searchTerm, '/') . ')/i',
            '<span class="bg-yellow-200 font-semibold">$1</span>',
            $text
        );
    }
}
@endphp