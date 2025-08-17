@extends('layouts.app')

@section('title', 'Products - SneakerFlash')

@section('content')
    <!-- Page Header -->
    <section class="bg-white py-6 border-b border-gray-200">
        <div class="container mx-auto px-4">
            <nav class="text-sm mb-4">
                <ol class="flex space-x-2 text-gray-600">
                    <li><a href="/" class="hover:text-blue-600">Home</a></li>
                    <li>/</li>
                    @if(request('category'))
                        <li><a href="{{ route('products.index') }}" class="hover:text-blue-600">Products</a></li>
                        <li>/</li>
                        <li class="text-gray-900 uppercase">{{ strtoupper(request('category')) }}</li>
                        @if(request('type'))
                            <li>/</li>
                            <li class="text-gray-900 capitalize">{{ ucfirst(str_replace('_', ' ', request('type'))) }}</li>
                        @endif
                    @else
                        <li class="text-gray-900">Products</li>
                    @endif
                </ol>
            </nav>
            
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    @if(request('category') && request('type'))
                        {{ strtoupper(request('category')) }} {{ ucfirst(str_replace('_', ' ', request('type'))) }}
                    @elseif(request('category'))
                        {{ strtoupper(request('category')) }} Products
                    @else
                        All Products
                    @endif
                </h1>
                <div class="text-gray-600">
                    {{ $total ?? 0 }} products found
                </div>
            </div>
        </div>
    </section>

    <!-- Category Tabs -->
    <section class="bg-white py-4 border-b border-gray-200">
        <div class="container mx-auto px-4">
            <div class="flex items-center space-x-4 overflow-x-auto">
                <!-- Filter Toggle Button -->
                <button id="filterToggle" class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-shrink-0">
                    <i class="fas fa-filter text-gray-600"></i>
                    <span class="text-sm font-medium text-gray-700">Filter</span>
                    <i id="filterIcon" class="fas fa-chevron-down text-gray-400 text-xs"></i>
                </button>

                <!-- Category Pills -->
                <div class="flex space-x-2 flex-shrink-0">
                    <a href="{{ route('products.index') }}" class="category-pill {{ !request('category') ? 'active' : '' }}">
                        All Products
                    </a>
                    <a href="{{ route('products.index', ['category' => 'mens']) }}" class="category-pill {{ request('category') === 'mens' ? 'active' : '' }}">
                        MENS
                    </a>
                    <a href="{{ route('products.index', ['category' => 'womens']) }}" class="category-pill {{ request('category') === 'womens' ? 'active' : '' }}">
                        WOMENS
                    </a>
                    <a href="{{ route('products.index', ['category' => 'unisex']) }}" class="category-pill {{ request('category') === 'unisex' ? 'active' : '' }}">
                        UNISEX
                    </a>
                    <a href="{{ route('products.index', ['sale' => 'true']) }}" class="category-pill {{ request('sale') ? 'active' : '' }} special">
                        SALE
                    </a>
                </div>

                <!-- Reset Filter -->
                <button onclick="clearFilters()" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700 flex-shrink-0">
                    <span>Reset</span>
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 py-8">
        <div class="flex gap-8">
            <!-- Filters Sidebar -->
            <aside id="filterSidebar" class="w-72 flex-shrink-0 hidden">
                <div class="bg-white rounded-2xl p-6 border border-gray-100">
                    <h3 class="font-semibold text-gray-900 mb-4">Filters</h3>
                    <!-- Add your filter content here -->
                </div>
            </aside>

            <!-- Products Grid -->
            <main class="flex-1">
                <!-- Sort Options & View Toggle -->
                <div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-600 font-medium">Sort by:</span>
                            <select name="sort" onchange="updateSort(this.value)" class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="latest" {{ request('sort') === 'latest' || !request('sort') ? 'selected' : '' }}>Latest</option>
                                <option value="name_az" {{ request('sort') === 'name_az' ? 'selected' : '' }}>Name A-Z</option>
                                <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                <option value="featured" {{ request('sort') === 'featured' ? 'selected' : '' }}>Featured</option>
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

                <!-- Products Grid -->
                <div id="productsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @if(isset($paginatedProducts) && $paginatedProducts->count() > 0)
                        @foreach($paginatedProducts as $product)
                            @php
                                // ⭐ CLEAN: Remove SKU parent from product name
                                $originalName = $product->name ?? 'Unknown Product';
                                $skuParent = $product->sku_parent ?? '';
                                
                                $cleanProductName = $originalName;
                                if (!empty($skuParent)) {
                                    // Remove SKU parent pattern like "- VN0A3HZFCAR"
                                    $cleanProductName = preg_replace('/\s*-\s*' . preg_quote($skuParent, '/') . '\s*/', '', $cleanProductName);
                                    $cleanProductName = preg_replace('/\s*' . preg_quote($skuParent, '/') . '\s*/', '', $cleanProductName);
                                }
                                
                                // ⭐ ADDITIONAL: Remove size patterns like "- Size M", "Size L", etc.
                                $cleanProductName = preg_replace('/\s*-\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanProductName);
                                $cleanProductName = preg_replace('/\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanProductName);
                                $cleanProductName = preg_replace('/\s*-\s*[A-Z0-9.]+\s*$/i', '', $cleanProductName);
                                
                                $cleanProductName = trim($cleanProductName, ' -');
                                
                                // Safe data handling
                                $productImages = is_array($product->images ?? null) ? $product->images : [];
                                $sizeVariants = [];
                                
                                if (isset($product->size_variants)) {
                                    if (is_array($product->size_variants)) {
                                        $sizeVariants = $product->size_variants;
                                    } elseif (is_object($product->size_variants)) {
                                        $sizeVariants = method_exists($product->size_variants, 'toArray') 
                                            ? $product->size_variants->toArray() 
                                            : (array) $product->size_variants;
                                    }
                                }
                                
                                $hasMultipleSizes = count($sizeVariants) > 1;
                                $productPrice = $product->price ?? 0;
                                $salePrice = $product->sale_price ?? null;
                                $totalStock = $product->total_stock ?? 0;
                            @endphp
                            
                            <div class="product-card bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-lg transition-all duration-300 group" 
                                 data-product-id="{{ $product->id ?? '' }}"
                                 data-sku-parent="{{ $product->sku_parent ?? '' }}"
                                 data-product-name="{{ $cleanProductName }}">
                                
                                <!-- Product Image -->
                                <div class="relative aspect-square bg-gray-50 overflow-hidden">
                                    @if(!empty($productImages))
                                        <img src="{{ $productImages[0] }}" 
                                             alt="{{ $cleanProductName }}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                             loading="lazy">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                            <i class="fas fa-shoe-prints text-4xl text-gray-300"></i>
                                        </div>
                                    @endif
                                    
                                    <!-- Product Badges -->
                                    <div class="absolute top-3 left-3 flex flex-col gap-2">
                                        @if($product->is_featured ?? false)
                                            <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                Featured
                                            </span>
                                        @endif
                                        @if($salePrice && $salePrice < $productPrice)
                                            @php
                                                $discount = round((($productPrice - $salePrice) / $productPrice) * 100);
                                            @endphp
                                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                -{{ $discount }}%
                                            </span>
                                        @endif
                                        @if($totalStock <= 0)
                                            <span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                Out of Stock
                                            </span>
                                        @elseif($totalStock < 10)
                                            <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                                Low Stock
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Wishlist Button -->
                                    <button class="wishlist-btn absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:shadow-lg transition-all duration-200" 
                                            data-product-id="{{ $product->id ?? '' }}"
                                            data-product-name="{{ $cleanProductName }}">
                                        <i class="wishlist-icon far fa-heart text-gray-400 transition-colors"></i>
                                    </button>
                                </div>
                                
                                <!-- Product Info -->
                                <div class="p-4">
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-500 uppercase tracking-wide">
                                            {{ strtoupper($product->product_type ?? 'APPAREL') }}
                                            @if($product->brand ?? false)
                                                • {{ $product->brand }}
                                            @endif
                                        </span>
                                    </div>
                                    
                                    {{-- ⭐ FIXED: Use clean product name --}}
                                    <h3 class="font-semibold text-gray-900 mb-3 text-sm leading-tight">
                                        <a href="{{ route('products.show', $product->slug ?? '#') }}" 
                                           class="hover:text-blue-600 transition-colors">
                                            {{ $cleanProductName }}
                                        </a>
                                    </h3>
                                    
                                    <!-- Size Options -->
                                    @if($hasMultipleSizes)
                                        <div class="mb-3">
                                            <span class="text-xs text-gray-500 font-medium">Available Sizes:</span>
                                            <div class="flex flex-wrap gap-1 mt-1" id="sizeContainer-{{ $product->id }}">
                                                @foreach($sizeVariants as $variant)
                                                    @php
                                                        $variantData = is_array($variant) ? $variant : (array) $variant;
                                                        $size = $variantData['size'] ?? 'Unknown';
                                                        $stock = (int) ($variantData['stock'] ?? 0);
                                                        $variantId = $variantData['id'] ?? '';
                                                        $sku = $variantData['sku'] ?? '';
                                                        $isAvailable = $stock > 0;
                                                    @endphp
                                                    <span class="size-badge text-xs px-2 py-1 rounded border {{ $isAvailable ? 'text-gray-700 bg-gray-50 border-gray-200 hover:bg-blue-50 hover:border-blue-300' : 'text-gray-400 bg-gray-100 border-gray-200 line-through' }}" 
                                                          data-size="{{ $size }}" 
                                                          data-stock="{{ $stock }}"
                                                          data-product-id="{{ $variantId }}"
                                                          data-sku="{{ $sku }}"
                                                          data-available="{{ $isAvailable ? 'true' : 'false' }}">
                                                        {{ $size }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Price -->
                                    <div class="mb-4 price-display">
                                        @if($salePrice && $salePrice < $productPrice)
                                            <div class="flex items-center space-x-2">
                                                <span class="text-lg font-bold text-red-600">
                                                    Rp {{ number_format($salePrice, 0, ',', '.') }}
                                                </span>
                                                <span class="text-sm text-gray-400 line-through">
                                                    Rp {{ number_format($productPrice, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-lg font-bold text-gray-900">
                                                Rp {{ number_format($productPrice, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- Stock Status -->
                                    <div class="mb-3 stock-display">
                                        @if($totalStock > 0)
                                            <span class="text-xs text-green-600 font-medium">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                In Stock ({{ $totalStock }} total)
                                            </span>
                                        @else
                                            <span class="text-xs text-red-600 font-medium">
                                                <i class="fas fa-times-circle mr-1"></i>
                                                Out of Stock
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- Add to Cart Button -->
                                    <div class="flex gap-2">
                                        @if($totalStock > 0)
                                            @if($hasMultipleSizes)
                                                {{-- ⭐ FIXED: Use clean product name in button data --}}
                                                <button type="button" 
                                                        class="flex-1 bg-gray-900 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors size-select-btn"
                                                        data-product-id="{{ $product->id ?? '' }}"
                                                        data-sku-parent="{{ $product->sku_parent ?? '' }}"
                                                        data-product-name="{{ $cleanProductName }}">
                                                    <i class="fas fa-shopping-cart mr-1"></i>
                                                    Select Size
                                                </button>
                                            @else
                                                <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form flex-1">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $product->id ?? '' }}">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" class="w-full bg-gray-900 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
                                                        <i class="fas fa-shopping-cart mr-1"></i>
                                                        Add to Cart
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <button disabled class="flex-1 bg-gray-300 text-gray-500 py-2 px-3 rounded-lg text-sm font-medium cursor-not-allowed">
                                                <i class="fas fa-times mr-1"></i>
                                                Out of Stock
                                            </button>
                                        @endif
                                        <a href="{{ route('products.show', $product->slug ?? '#') }}" 
                                           class="px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
                                            <i class="fas fa-eye text-gray-600"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Empty State -->
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-shoe-prints text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No products found</h3>
                            <p class="text-gray-500 mb-4">Try adjusting your filters or search terms</p>
                            <button onclick="clearFilters()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Clear All Filters
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Pagination -->
                @if(isset($total) && $total > 0)
                <div class="mt-8">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ (($currentPage ?? 1) - 1) * ($perPage ?? 12) + 1 }} to {{ min(($currentPage ?? 1) * ($perPage ?? 12), $total) }} of {{ $total }} results
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            @if(($currentPage ?? 1) > 1)
                                <a href="{{ request()->fullUrlWithQuery(['page' => ($currentPage ?? 1) - 1]) }}" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    Previous
                                </a>
                            @endif
                            
                            @for($i = max(1, ($currentPage ?? 1) - 2); $i <= min(($lastPage ?? 1), ($currentPage ?? 1) + 2); $i++)
                                <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" 
                                   class="px-3 py-2 text-sm font-medium {{ $i == ($currentPage ?? 1) ? 'text-white bg-blue-600 border-blue-600' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50' }} border rounded-md">
                                    {{ $i }}
                                </a>
                            @endfor
                            
                            @if(($currentPage ?? 1) < ($lastPage ?? 1))
                                <a href="{{ request()->fullUrlWithQuery(['page' => ($currentPage ?? 1) + 1]) }}" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    Next
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </main>
        </div>
    </div>

    <!-- Enhanced Size Selection Modal -->
    <div id="sizeSelectionModal" class="fixed inset-0 z-50 hidden">
        <!-- Background Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        
        <!-- Modal Container -->
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl max-w-lg w-full mx-auto shadow-2xl transform transition-all">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 rounded-t-2xl border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900" id="modalProductName">Select Size</h3>
                            <p class="text-sm text-gray-500 mt-1">Choose your preferred size</p>
                        </div>
                        <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded-full p-2 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Size Options Grid -->
                    <div class="mb-6">
                        <div id="sizeOptionsContainer" class="grid grid-cols-4 gap-3">
                            <!-- Size options will be populated here -->
                        </div>
                    </div>
                    
                    <!-- Selected Size Info -->
                    <div class="mb-6 hidden" id="selectedSizeInfo">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-blue-900">Selected Size:</span>
                                <span id="selectedSizeDisplay" class="text-lg font-bold text-blue-700"></span>
                            </div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-blue-600">Available Stock:</span>
                                <span id="selectedSizeStock" class="text-sm font-medium text-blue-700"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-600">Price:</span>
                                <span id="selectedSizePrice" class="text-sm font-semibold text-blue-700">Rp 649,000</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add to Cart Form -->
                    <form id="sizeAddToCartForm" action="{{ route('cart.add') }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="product_id" id="selectedProductId">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="size" id="selectedSizeValue">
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Add to Cart
                        </button>
                    </form>
                </div>
                
                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-3 rounded-b-2xl">
                    <p class="text-xs text-center text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Select a size to continue with your purchase
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toastNotification" class="fixed top-4 right-4 z-50 hidden">
        <div class="bg-white border border-gray-200 rounded-lg shadow-lg p-4 min-w-80">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i id="toastIcon" class="fas fa-check-circle text-green-500"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p id="toastMessage" class="text-sm font-medium text-gray-900"></p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button onclick="hideToast()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ENHANCED JAVASCRIPT - Size Selection with Clean Product Names -->
    <script>
        console.log('🚀 Enhanced JavaScript Loading with Clean Product Names...');

        // Wait for everything to load
        window.addEventListener('load', function() {
            console.log('✅ Window loaded - initializing...');
            
            // Force check every 100ms until elements are ready
            var checkInterval = setInterval(function() {
                var buttons = document.querySelectorAll('.size-select-btn');
                var modal = document.getElementById('sizeSelectionModal');
                
                if (buttons.length > 0 && modal) {
                    console.log('✅ Elements found - setting up events');
                    clearInterval(checkInterval);
                    setupSizeSelection();
                }
            }, 100);
            
            // Timeout after 5 seconds
            setTimeout(function() {
                clearInterval(checkInterval);
                console.log('⚠️ Timeout - setting up anyway');
                setupSizeSelection();
            }, 5000);
        });

        function setupSizeSelection() {
            console.log('🔧 Setting up size selection with clean product names...');
            
            // Remove any existing listeners first
            document.removeEventListener('click', handleDocumentClick);
            
            // Add fresh listeners
            document.addEventListener('click', handleDocumentClick);
            
            // Add direct listeners to buttons as backup
            var buttons = document.querySelectorAll('.size-select-btn');
            console.log('Found', buttons.length, 'size select buttons');
            
            buttons.forEach(function(button, index) {
                console.log('Setting up button', index + 1, 'with clean name:', button.dataset.productName);
                
                // Remove existing listeners
                button.removeEventListener('click', handleButtonClick);
                
                // Add new listener
                button.addEventListener('click', handleButtonClick);
                
                // Also add onclick as backup
                button.onclick = function(e) {
                    e.preventDefault();
                    console.log('📱 Backup onclick triggered with clean name:', this.dataset.productName);
                    handleButtonClick.call(this, e);
                };
            });
            
            console.log('✅ Size selection setup complete with clean product names');
        }

        function handleDocumentClick(e) {
            // Size select button
            if (e.target.closest('.size-select-btn')) {
                e.preventDefault();
                console.log('🎯 Document click - size button');
                var button = e.target.closest('.size-select-btn');
                openSizeModal(button);
                return;
            }
            
            // Modal close
            if (e.target.id === 'closeModalBtn' || e.target.closest('#closeModalBtn')) {
                console.log('❌ Close button clicked');
                closeModal();
                return;
            }
            
            // Background close
            if (e.target.id === 'sizeSelectionModal') {
                console.log('❌ Background clicked');
                closeModal();
                return;
            }
            
            // Size option
            if (e.target.closest('.size-option')) {
                var option = e.target.closest('.size-option');
                if (!option.classList.contains('disabled')) {
                    console.log('📏 Size option clicked:', option.dataset.size);
                    selectSize(option);
                }
                return;
            }
        }

        function handleButtonClick(e) {
            e.preventDefault();
            console.log('🎯 Direct button click triggered with clean name:', this.dataset.productName);
            openSizeModal(this);
        }

        function openSizeModal(button) {
            console.log('🔓 Opening size modal...');
            
            var productId = button.getAttribute('data-product-id');
            
            // ⭐ Use clean product name from data attribute
            var cleanProductName = button.getAttribute('data-product-name');
            
            console.log('Product:', productId, 'Clean Name:', cleanProductName);
            
            var modal = document.getElementById('sizeSelectionModal');
            var title = document.getElementById('modalProductName');
            var container = document.getElementById('sizeOptionsContainer');
            
            if (!modal) {
                console.error('❌ Modal not found!');
                alert('Modal not found!');
                return;
            }
            
            // ⭐ Set title with clean product name
            if (title) {
                title.textContent = 'Select Size - ' + cleanProductName;
            }
            
            // Get sizes from product card
            var productCard = button.closest('.product-card');
            var sizeContainer = productCard ? productCard.querySelector('#sizeContainer-' + productId) : null;
            
            var sizes = [];
            if (sizeContainer) {
                var badges = sizeContainer.querySelectorAll('.size-badge');
                badges.forEach(function(badge) {
                    sizes.push({
                        size: badge.getAttribute('data-size'),
                        stock: badge.getAttribute('data-stock'),
                        available: badge.getAttribute('data-available') === 'true',
                        productId: badge.getAttribute('data-product-id')
                    });
                });
            }
            
            console.log('Found sizes:', sizes);
            
            // Populate sizes
            if (container) {
                container.innerHTML = '';
                
                if (sizes.length > 0) {
                    sizes.forEach(function(size) {
                        var div = document.createElement('div');
                        div.className = 'size-option' + (size.available ? '' : ' disabled');
                        div.setAttribute('data-size', size.size);
                        div.setAttribute('data-stock', size.stock);
                        div.setAttribute('data-product-id', size.productId);
                        
                        div.innerHTML = 
                            '<div class="size-label" style="font-weight: 600; font-size: 14px;">' + size.size + '</div>' +
                            '<div class="stock-info" style="font-size: 11px; margin-top: 2px; color: ' + (size.available ? '#059669' : '#dc2626') + ';">' +
                            (size.available ? size.stock + ' left' : 'Out of stock') + '</div>';
                        
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<div style="grid-column: span 3; text-align: center; color: #6b7280;">No sizes available</div>';
                }
            }
            
            // Show modal
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            console.log('✅ Modal displayed with clean product name:', cleanProductName);
        }

        function selectSize(element) {
            console.log('📏 Selecting size:', element.getAttribute('data-size'));
            
            // Clear previous selections
            document.querySelectorAll('.size-option').forEach(function(opt) {
                opt.classList.remove('selected');
                opt.style.backgroundColor = '';
                opt.style.color = '';
                opt.style.borderColor = '';
            });
            
            // Select current
            element.classList.add('selected');
            element.style.backgroundColor = '#3b82f6';
            element.style.color = 'white';
            element.style.borderColor = '#3b82f6';
            
            var size = element.getAttribute('data-size');
            var stock = element.getAttribute('data-stock');
            var productId = element.getAttribute('data-product-id');
            
            // Update form
            var sizeInfo = document.getElementById('selectedSizeInfo');
            var sizeDisplay = document.getElementById('selectedSizeDisplay');
            var sizeStock = document.getElementById('selectedSizeStock');
            var form = document.getElementById('sizeAddToCartForm');
            var productInput = document.getElementById('selectedProductId');
            var sizeInput = document.getElementById('selectedSizeValue');
            
            if (sizeDisplay) sizeDisplay.textContent = size;
            if (sizeStock) sizeStock.textContent = stock + ' available';
            if (productInput) productInput.value = productId;
            if (sizeInput) sizeInput.value = size;
            
            if (sizeInfo) sizeInfo.classList.remove('hidden');
            if (form) form.classList.remove('hidden');
            
            console.log('📝 Form updated with:', productId, size);
        }

        function closeModal() {
            console.log('❌ Closing modal...');
            
            var modal = document.getElementById('sizeSelectionModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = '';
                
                // Reset form
                var sizeInfo = document.getElementById('selectedSizeInfo');
                var form = document.getElementById('sizeAddToCartForm');
                if (sizeInfo) sizeInfo.classList.add('hidden');
                if (form) form.classList.add('hidden');
                
                // Clear selections
                document.querySelectorAll('.size-option').forEach(function(opt) {
                    opt.classList.remove('selected');
                    opt.style.backgroundColor = '';
                    opt.style.color = '';
                    opt.style.borderColor = '';
                });
            }
        }

        // Handle form submission
        document.addEventListener('submit', function(e) {
            if (e.target.id === 'sizeAddToCartForm') {
                e.preventDefault();
                console.log('🛒 Form submitted');
                
                var form = e.target;
                var button = form.querySelector('button[type="submit"]');
                var originalText = button.innerHTML;
                
                // Get CSRF token
                var csrf = document.querySelector('meta[name="csrf-token"]');
                if (!csrf) {
                    alert('CSRF token missing. Please refresh the page.');
                    return;
                }
                
                // Show loading
                button.innerHTML = '⏳ Adding...';
                button.disabled = true;
                
                // Create form data
                var formData = new FormData(form);
                
                console.log('📦 Sending data:', {
                    productId: formData.get('product_id'),
                    size: formData.get('size'),
                    quantity: formData.get('quantity')
                });
                
                // Submit via fetch
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrf.getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(response) {
                    console.log('📡 Response status:', response.status);
                    console.log('📡 Response headers:', response.headers.get('content-type'));
                    
                    // Check if response is JSON
                    var contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // If not JSON, return the text to see what's wrong
                        return response.text().then(function(text) {
                            console.error('❌ Non-JSON response:', text.substring(0, 500));
                            throw new Error('Server returned HTML instead of JSON. Response: ' + text.substring(0, 100));
                        });
                    }
                })
                .then(function(data) {
                    console.log('📦 JSON Data:', data);
                    
                    if (data.success) {
                        button.innerHTML = '✅ Added!';
                        button.style.backgroundColor = '#10b981';
                        showToast('Product added to cart!');
                        
                        // Update cart counter if provided
                        if (data.cart_count) {
                            updateCartCounter(data.cart_count);
                        }
                        
                        setTimeout(function() {
                            closeModal();
                        }, 1500);
                    } else {
                        button.innerHTML = '❌ Failed';
                        showToast(data.message || 'Failed to add to cart', 'error');
                    }
                })
                .catch(function(error) {
                    console.error('💥 Add to cart error:', error);
                    button.innerHTML = '❌ Error';
                    
                    // More specific error messages
                    if (error.message.includes('HTML instead of JSON')) {
                        showToast('Server error - please check if cart route exists', 'error');
                    } else if (error.message.includes('Failed to fetch')) {
                        showToast('Network error - please check your connection', 'error');
                    } else {
                        showToast('Error: ' + error.message, 'error');
                    }
                })
                .finally(function() {
                    setTimeout(function() {
                        button.innerHTML = originalText;
                        button.disabled = false;
                        button.style.backgroundColor = '';
                    }, 3000);
                });
            }
        });

        // ESC key handler
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        function showToast(message, type) {
            var toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white transition-all duration-300 ' + 
                             (type === 'error' ? 'bg-red-500' : 'bg-green-500');
            
            toast.innerHTML = '<div style="display: flex; align-items: center; gap: 8px;"><span>' + message + '</span></div>';
            
            document.body.appendChild(toast);
            
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        function updateCartCounter(count) {
            var cartCounters = document.querySelectorAll('.cart-counter, [data-cart-count], .cart-badge');
            cartCounters.forEach(function(counter) {
                counter.textContent = count;
                if (count > 0) {
                    counter.style.display = 'inline';
                } else {
                    counter.style.display = 'none';
                }
            });
            console.log('🔢 Cart counter updated to:', count);
        }

        // Utility functions
        function updateSort(value) {
            var url = new URL(window.location);
            url.searchParams.set('sort', value);
            window.location = url;
        }

        function clearFilters() {
            window.location = window.location.pathname;
        }

        // Initialize wishlist functionality
        function initWishlist() {
            document.querySelectorAll('.wishlist-btn').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var productId = this.dataset.productId;
                    var cleanProductName = this.dataset.productName; // ⭐ Using clean product name
                    
                    console.log('❤️ Wishlist clicked for clean product:', cleanProductName);
                    
                    // Toggle wishlist functionality here
                    var icon = this.querySelector('.wishlist-icon');
                    if (icon.classList.contains('far')) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        icon.style.color = '#ef4444';
                        showToast(cleanProductName + ' added to wishlist!', 'success');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        icon.style.color = '#9ca3af';
                        showToast(cleanProductName + ' removed from wishlist!', 'info');
                    }
                });
            });
        }

        // Initialize filter toggle
        function initFilterToggle() {
            var filterToggle = document.getElementById('filterToggle');
            var filterSidebar = document.getElementById('filterSidebar');
            var filterIcon = document.getElementById('filterIcon');
            
            if (filterToggle && filterSidebar && filterIcon) {
                var filterVisible = false;
                
                filterToggle.addEventListener('click', function() {
                    filterVisible = !filterVisible;
                    
                    if (filterVisible) {
                        filterSidebar.classList.remove('hidden');
                        filterIcon.classList.remove('fa-chevron-down');
                        filterIcon.classList.add('fa-chevron-up');
                    } else {
                        filterSidebar.classList.add('hidden');
                        filterIcon.classList.remove('fa-chevron-up');
                        filterIcon.classList.add('fa-chevron-down');
                    }
                });
            }
        }

        // Initialize view toggle
        function initViewToggle() {
            var gridViewBtn = document.getElementById('gridView');
            var listViewBtn = document.getElementById('listView');
            var productsContainer = document.getElementById('productsContainer');
            
            if (gridViewBtn && listViewBtn && productsContainer) {
                gridViewBtn.addEventListener('click', function() {
                    productsContainer.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6';
                    this.classList.add('text-blue-600', 'bg-blue-50');
                    this.classList.remove('text-gray-400');
                    listViewBtn.classList.remove('text-blue-600', 'bg-blue-50');
                    listViewBtn.classList.add('text-gray-400');
                });
                
                listViewBtn.addEventListener('click', function() {
                    productsContainer.className = 'space-y-4';
                    this.classList.add('text-blue-600', 'bg-blue-50');
                    this.classList.remove('text-gray-400');
                    gridViewBtn.classList.remove('text-blue-600', 'bg-blue-50');
                    gridViewBtn.classList.add('text-gray-400');
                });
            }
        }

        // Initialize all functionality when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            initWishlist();
            initFilterToggle();
            initViewToggle();
            console.log('✅ All functionality initialized with clean product names');
        });

        console.log('🎯 Enhanced JavaScript Loaded - Size Selection Ready with Clean Product Names!');
    </script>
@endsection

@push('styles')
<style>
    /* Category Pills */
    .category-pill {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        color: #6c757d;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .category-pill:hover {
        background: #e9ecef;
        color: #495057;
        border-color: #adb5bd;
    }

    .category-pill.active {
        background: #000000;
        color: #ffffff;
        border-color: #000000;
    }

    .category-pill.special {
        color: #ff4757;
        border-color: #ff4757;
    }

    .category-pill.special.active {
        background: #ff4757;
        color: #ffffff;
    }

    /* Size Badge Styles */
    .size-badge {
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .size-badge.line-through {
        cursor: not-allowed;
    }

    /* Enhanced Size Selection Modal Styles */
    #sizeSelectionModal {
        backdrop-filter: blur(8px);
    }
    
    #sizeSelectionModal .relative {
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    /* Size Options Connected with Separator */
    #sizeOptionsContainer {
        display: flex;
        justify-content: center;
        max-width: 300px;
        margin: 0 auto;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .size-option {
        flex: 1;
        padding: 16px 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: white;
        min-height: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        position: relative;
        border: none;
        border-radius: 0;
    }

    /* Separator line between options */
    .size-option:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 10%;
        bottom: 10%;
        width: 2px;
        background-color: #d1d5db;
        transition: background-color 0.2s ease;
    }

    .size-option:hover:not(.disabled) {
        background-color: #f0f9ff;
        color: #1d4ed8;
    }

    .size-option:hover:not(.disabled):not(:last-child)::after {
        background-color: #3b82f6;
    }

    .size-option.selected {
        background: #2563eb !important;
        color: white !important;
    }

    .size-option.selected:not(:last-child)::after {
        background-color: #1d4ed8;
    }

    .size-option.selected .size-label {
        color: white !important;
        font-weight: 700;
    }

    .size-option.selected .stock-info {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .size-option.disabled {
        background-color: #f9fafb;
        color: #9ca3af;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .size-option.disabled:not(:last-child)::after {
        background-color: #e5e7eb;
    }

    .size-option .size-label {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }

    .size-option .stock-info {
        font-size: 11px;
        font-weight: 500;
        color: #6b7280;
    }
    
    /* Mobile adjustments */
    @media (max-width: 640px) {
        #sizeSelectionModal .relative {
            margin: 1rem;
            max-width: none;
        }
        
        #sizeOptionsContainer {
            max-width: 250px;
        }
        
        .size-option {
            min-height: 50px;
            padding: 12px 8px;
        }
        
        .size-option .size-label {
            font-size: 14px;
        }
        
        .size-option .stock-info {
            font-size: 10px;
        }
    }
    
    /* Mobile adjustments */
    @media (max-width: 640px) {
        #sizeSelectionModal .relative {
            margin: 1rem;
            max-width: none;
        }
        
        #sizeOptionsContainer {
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        
        .size-option {
            min-height: 70px;
            padding: 12px 6px;
        }
        
        .size-option .size-label {
            font-size: 14px;
        }
    }

    /* Wishlist button active state */
    .wishlist-btn.active {
        background-color: #fef2f2;
        border-color: #fecaca;
    }

    /* Color classes */
    .text-green-600 { color: #059669; }
    .text-red-600 { color: #dc2626; }
    .text-blue-600 { color: #2563eb; }
    .text-blue-700 { color: #1d4ed8; }
    .text-blue-900 { color: #1e3a8a; }

    /* Toast Animation */
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .fixed.top-4.right-4 { animation: slideInRight 0.3s ease-out; }
</style>
@endpush