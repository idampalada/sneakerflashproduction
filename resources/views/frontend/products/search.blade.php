@extends('layouts.app')

@section('title', 'Search Results - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header dengan search term dan count -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Search Results</h1>
        @if(isset($searchQuery) && !empty($searchQuery))
            <p class="text-lg text-gray-600">
                Results for: <span class="font-semibold">"{{ $searchQuery }}"</span> 
                ({{ $total ?? 0 }} products found)
            </p>
        @else
            <p class="text-lg text-gray-600">No search term provided.</p>
        @endif
    </div>

    <!-- Main Content: Grid + Sidebar -->
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <div class="lg:w-1/4">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                <!-- Search Within Results -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Search Within Results</h3>
                    <form method="GET" action="{{ request()->url() }}" class="space-y-3">
                        {{-- Hidden untuk maintain q dan filter lain --}}
                        <input type="hidden" name="q" value="{{ $searchQuery ?? request('q') }}">
                        @if(request()->hasAny(['category', 'brands', 'min_price', 'max_price', 'sort']))
                            @foreach(request()->only(['category', 'brands', 'min_price', 'max_price', 'sort']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $val)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $val }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                        @endif
                        
                        <input type="text" 
                               name="search" 
                               placeholder="Refine search..." 
                               value="{{ request('search') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                            Refine
                        </button>
                    </form>
                </div>

                <!-- Categories -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Categories</h3>
                    <ul class="space-y-2">
                        @foreach($categories ?? [] as $category)
                            <li>
                                <a href="{{ request()->url() }}?q={{ urlencode($searchQuery ?? request('q')) }}&category={{ $category->slug }}" 
                                   class="text-sm text-gray-600 hover:text-blue-600 flex items-center {{ request('category') == $category->slug ? 'font-semibold text-blue-600' : '' }}">
                                    <i class="fas fa-chevron-right mr-2 text-xs"></i>
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Brands (Checkbox Array) -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Brands</h3>
                    <form method="GET" action="{{ request()->url() }}" class="space-y-2">
                        <input type="hidden" name="q" value="{{ $searchQuery ?? request('q') }}">
                        @if(request()->hasAny(['category', 'min_price', 'max_price', 'sort']))
                            @foreach(request()->only(['category', 'min_price', 'max_price', 'sort']) as $key => $value)
                                @if(is_array($value) && $key !== 'brands')
                                    @foreach($value as $val)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $val }}">
                                    @endforeach
                                @elseif($key !== 'brands')
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                        @endif
                        
                        @foreach($brands ?? [] as $brand)
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" 
                                       name="brands[]" 
                                       value="{{ $brand }}" 
                                       {{ in_array($brand, request('brands', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-600">{{ $brand }}</span>
                            </label>
                        @endforeach
                        <button type="submit" class="w-full bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 mt-2">
                            Apply Brands
                        </button>
                    </form>
                </div>

                <!-- Price Range -->
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Price Range</h3>
                    <form method="GET" action="{{ request()->url() }}" class="space-y-3">
                        <input type="hidden" name="q" value="{{ $searchQuery ?? request('q') }}">
                        @if(request()->hasAny(['category', 'brands', 'sort']))
                            @foreach(request()->only(['category', 'brands', 'sort']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $val)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $val }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                        @endif
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Min Price</label>
                            <input type="number" name="min_price" value="{{ request('min_price') }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Price</label>
                            <input type="number" name="max_price" value="{{ request('max_price') }}" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                            Apply Price
                        </button>
                    </form>
                </div>

                <!-- Sort -->
                <div>
                    <h3 class="font-semibold text-gray-800 mb-3">Sort By</h3>
                    <form method="GET" action="{{ request()->url() }}" class="space-y-2" id="sortForm">
                        <input type="hidden" name="q" value="{{ $searchQuery ?? request('q') }}">
                        @if(request()->hasAny(['category', 'brands', 'min_price', 'max_price']))
                            @foreach(request()->only(['category', 'brands', 'min_price', 'max_price']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $val)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $val }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                        @endif
                        
                        <select name="sort" onchange="document.getElementById('sortForm').submit();" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="relevance" {{ request('sort', 'relevance') == 'relevance' ? 'selected' : '' }}>Relevance</option>
                            <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                            <option value="name_az" {{ request('sort') == 'name_az' ? 'selected' : '' }}>Name: A-Z</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="lg:w-3/4">
            @if(isset($paginatedProducts) && $paginatedProducts->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($paginatedProducts as $product)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <!-- Product Image -->
                            <div class="relative h-48 bg-gray-100">
                                @if($product->images && is_array($product->images) && count($product->images) > 0)
                                    <img src="{{ asset('storage/' . $product->images[0]) }}" 
                                         alt="{{ $product->name }}" 
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="fas fa-image text-3xl"></i>
                                    </div>
                                @endif
                                
                                <!-- Sale Badge -->
                                @if(isset($product->sale_price) && $product->sale_price < ($product->price ?? 0))
                                    <div class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">
                                        Sale
                                    </div>
                                @endif
                            </div>

                            <!-- Product Info -->
                            <div class="p-4">
                                <!-- Brand & Name (Highlight via JS) -->
                                <div class="mb-2">
                                    @if($product->brand)
                                        <span class="text-sm text-gray-500">{{ $product->brand }}</span>
                                    @endif
                                    <h3 class="font-semibold text-gray-900 mt-1 line-clamp-2 product-name">
                                        {{ $product->name }}
                                    </h3>
                                </div>

                                <!-- Price -->
                                <div class="mb-3">
                                    @if(isset($product->sale_price) && $product->sale_price < ($product->price ?? 0))
                                        <span class="text-lg font-bold text-red-600">Rp {{ number_format($product->sale_price) }}</span>
                                        <span class="text-sm text-gray-500 line-through ml-2">Rp {{ number_format($product->price) }}</span>
                                    @else
                                        <span class="text-lg font-bold text-gray-900">Rp {{ number_format($product->price ?? 0) }}</span>
                                    @endif
                                </div>

                                <!-- Stock & Sizes -->
                                <div class="mb-3 text-sm">
                                    @if(isset($product->total_stock) && $product->total_stock > 0)
                                        <span class="text-green-600">In Stock ({{ $product->total_stock }})</span>
                                    @else
                                        <span class="text-red-600">Out of Stock</span>
                                    @endif
                                    
                                    @if(isset($product->has_multiple_sizes) && $product->has_multiple_sizes)
                                        <div class="mt-1 text-xs text-gray-500">
                                            Sizes: {{ $product->size_variants->pluck('size')->implode(', ') }}
                                        </div>
                                    @endif
                                </div>

                                <!-- View Details Button -->
                                <a href="{{ route('products.show', $product->slug) }}" 
                                   class="w-full block bg-blue-600 text-white py-2 px-4 rounded-md text-center hover:bg-blue-700 transition-colors">
                                    View Details
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Custom Pagination -->
                @if(isset($total) && $total > ($perPage ?? 12))
                    <div class="mt-8 flex justify-center">
                        <nav class="flex space-x-2">
                            @if(($currentPage ?? 1) > 1)
                                <a href="{{ request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => ($currentPage ?? 1) - 1])) }}" 
                                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Previous</a>
                            @endif
                            
                            @php
                                $start = max(1, ($currentPage ?? 1) - 2);
                                $end = min(($lastPage ?? 1), ($currentPage ?? 1) + 2);
                            @endphp
                            @for($i = $start; $i <= $end; $i++)
                                <a href="{{ request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => $i])) }}" 
                                   class="px-4 py-2 {{ ($currentPage ?? 1) == $i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }} rounded-lg hover:bg-gray-300">
                                    {{ $i }}
                                </a>
                            @endfor
                            
                            @if(($currentPage ?? 1) < ($lastPage ?? 1))
                                <a href="{{ request()->url() . '?' . http_build_query(array_merge(request()->query(), ['page' => ($currentPage ?? 1) + 1])) }}" 
                                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Next</a>
                            @endif
                        </nav>
                    </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="text-center py-12">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-semibold text-gray-600 mb-2">No products found</h2>
                    <p class="text-gray-500 mb-6">
                        @if(isset($searchQuery) && !empty($searchQuery))
                            No products match "<strong>{{ $searchQuery }}</strong>". Try different keywords.
                        @else
                            No products available. Try searching for something else.
                        @endif
                    </p>
                    <a href="{{ route('products.index') }}" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700">
                        Browse All Products
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Fallback highlight (client-side JS) - Tanpa PHP helper
    document.addEventListener('DOMContentLoaded', function() {
        const searchTerm = '{{ $searchQuery ?? request("q") ?? "" }}';
        if (searchTerm && searchTerm.trim() !== '') {
            const productNames = document.querySelectorAll('.product-name');
            productNames.forEach(function(element) {
                const text = element.textContent || element.innerText;
                // Fix regex: Escape special chars dengan benar
                const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\$\\$/g, '\\$&');
                const regex = new RegExp(`(${escapedTerm})`, 'gi');
                const highlighted = text.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
                if (highlighted !== text) {
                    element.innerHTML = highlighted;
                }
            });
        }

        // Auto-submit sort form (konsistensi dengan onchange)
        const sortSelects = document.querySelectorAll('select[name="sort"]');
        sortSelects.forEach(function(select) {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });

        // Optional: Loading state untuk form submit (UX sederhana)
        const forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.textContent;
                    submitBtn.textContent = 'Loading...';
                    submitBtn.disabled = true;
                    // Reset setelah 3 detik (bisa di-improve)
                    setTimeout(() => {
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                }
            });
        });
    });
</script>
@endpush