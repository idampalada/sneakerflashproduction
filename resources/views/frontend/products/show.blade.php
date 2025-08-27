@extends('layouts.app')

@section('title', $product->name . ' - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-6">
        <ol class="flex space-x-2 text-gray-600">
            <li><a href="/" class="hover:text-blue-600">Home</a></li>
            <li>/</li>
            <li><a href="{{ route('products.index') }}" class="hover:text-blue-600">Products</a></li>
            <li>/</li>
            @if($product->category)
                <li><a href="{{ route('categories.show', $product->category->slug) }}" class="hover:text-blue-600">{{ $product->category->name }}</a></li>
                <li>/</li>
            @endif
            <li class="text-gray-900">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Product Images -->
        <div class="space-y-4">
            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                @if($product->images && count($product->images) > 0)
                    @php
                        $mainImageUrl = $product->featured_image;
                        if (!filter_var($mainImageUrl, FILTER_VALIDATE_URL)) {
                            $mainImageUrl = asset('storage/' . ltrim($mainImageUrl, '/'));
                        }
                    @endphp
                    <img id="mainImage" src="{{ $mainImageUrl }}" 
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover"
                         onerror="this.src='{{ asset('images/default-product.jpg') }}'">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-image text-6xl text-gray-400"></i>
                    </div>
                @endif
            </div>
            
            @if($product->images && count($product->images) > 1)
                <div class="grid grid-cols-4 gap-2">
                    @foreach($product->images as $index => $image)
                        @php
                            // Handle both external URLs and storage paths
                            if (filter_var($image, FILTER_VALIDATE_URL)) {
                                $imageUrl = $image;
                            } else {
                                $imageUrl = asset('storage/' . ltrim($image, '/'));
                            }
                        @endphp
                        <button class="thumbnail aspect-square bg-gray-100 rounded-lg overflow-hidden hover:ring-2 hover:ring-blue-500 transition-all {{ $index === 0 ? 'ring-2 ring-blue-500' : '' }}"
                                data-image="{{ $imageUrl }}"
                                onclick="changeMainImage('{{ $imageUrl }}', this)">
                            <img src="{{ $imageUrl }}" 
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='{{ asset('images/default-product.jpg') }}'">
                        </button>
                    @endforeach
                </div>
                
                <div class="text-center text-sm text-gray-500">
                    {{ count($product->images) }} images available
                </div>
            @endif
        </div>

        <!-- Product Info -->
        <div class="space-y-6">
            <div>
                @if($product->category)
                    <p class="text-sm text-gray-500 mb-2">
                        {{ $product->category->name }}
                        @if($product->brand)
                            • {{ $product->brand }}
                        @endif
                    </p>
                @endif
                
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
                @endphp
                
                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $cleanProductName }}</h1>
                
                <!-- Product Tags -->
                <div class="flex flex-wrap gap-2 mb-4">
                    @if($product->product_type)
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                            {{ ucfirst(str_replace('_', ' ', $product->product_type)) }}
                        </span>
                    @endif
                    @if($product->gender_target && is_array($product->gender_target))
                        @foreach($product->gender_target as $gender)
                            <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">
                                @switch($gender)
                                    @case('mens')
                                        👨 Men's
                                        @break
                                    @case('womens')
                                        👩 Women's
                                        @break
                                    @case('unisex')
                                        🌐 Unisex
                                        @break
                                    @default
                                        {{ ucfirst($gender) }}
                                @endswitch
                            </span>
                        @endforeach
                    @endif
                </div>

                <!-- SKU Information -->
                @if($product->sku)
                    <div class="text-sm text-gray-500 mb-4">
                        <p><strong>SKU:</strong> {{ $product->sku }}</p>
                    </div>
                @endif
            </div>

            <!-- Price -->
            <div class="space-y-2">
                @if($product->sale_price && $product->sale_price < $product->price)
                    <div class="flex items-center space-x-3">
                        <span class="text-3xl font-bold text-red-600" id="currentPrice">
                            Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                        </span>
                        <span class="text-xl text-gray-500 line-through" id="originalPrice">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </span>
                        <span class="bg-red-100 text-red-800 text-sm px-2 py-1 rounded-full">
                            Save {{ round((($product->price - $product->sale_price) / $product->price) * 100) }}%
                        </span>
                    </div>
                @else
                    <span class="text-3xl font-bold text-gray-900" id="currentPrice">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </span>
                @endif
            </div>

            <!-- ⭐ ENHANCED: Size Selection with Clean Display -->
            @if(($product->available_sizes && count($product->available_sizes) > 0) || (isset($sizeVariants) && $sizeVariants->count() > 0))
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Select Size:</h3>
                    <div class="flex flex-wrap gap-3" id="sizeOptionsContainer">
                        @php
                            $allSizes = collect();
                            
                            // Add current product size
                            if ($product->available_sizes && count($product->available_sizes) > 0) {
                                foreach ($product->available_sizes as $size) {
                                    $allSizes->push([
                                        'size' => $size,
                                        'product_id' => $product->id,
                                        'stock' => $product->stock_quantity ?? 0,
                                        'price' => $product->sale_price ?: $product->price,
                                        'original_price' => $product->price,
                                        'sku' => $product->sku,
                                        'available' => ($product->stock_quantity ?? 0) > 0,
                                        'is_current' => true
                                    ]);
                                }
                            }
                            
                            // Add size variants
                            if (isset($sizeVariants)) {
                                foreach ($sizeVariants as $variant) {
                                    $allSizes->push([
                                        'size' => $variant['size'],
                                        'product_id' => $variant['id'],
                                        'stock' => $variant['stock'],
                                        'price' => $variant['price'],
                                        'original_price' => $variant['original_price'],
                                        'sku' => $variant['sku'],
                                        'available' => $variant['available'],
                                        'is_current' => false
                                    ]);
                                }
                            }
                            
                            // Sort by size
                            $allSizes = $allSizes->sortBy('size')->unique('size');
                        @endphp
                        
                        @foreach($allSizes as $sizeOption)
                            <button type="button" 
                                    class="size-option px-4 py-3 border-2 rounded-lg text-center transition-all {{ $sizeOption['available'] ? 'border-gray-300 hover:border-blue-500 hover:bg-blue-50 text-gray-700' : 'border-gray-200 bg-gray-100 cursor-not-allowed opacity-50 text-gray-400' }} {{ $sizeOption['is_current'] ? 'border-blue-500 bg-blue-50' : '' }}"
                                    data-size="{{ $sizeOption['size'] }}"
                                    data-product-id="{{ $sizeOption['product_id'] }}"
                                    data-stock="{{ $sizeOption['stock'] }}"
                                    data-price="{{ $sizeOption['price'] }}"
                                    data-original-price="{{ $sizeOption['original_price'] }}"
                                    data-sku="{{ $sizeOption['sku'] }}"
                                    data-available="{{ $sizeOption['available'] ? 'true' : 'false' }}"
                                    {{ $sizeOption['available'] ? '' : 'disabled' }}
                                    onclick="selectSize(this)">
                                <div class="font-semibold text-sm">{{ $sizeOption['size'] }}</div>
                            </button>
                        @endforeach
                    </div>
                    
                    <!-- Selected Size Info -->
                    <div id="selectedSizeInfo" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg {{ ($product->available_sizes && count($product->available_sizes) > 0) ? '' : 'hidden' }}">
                        <div class="flex justify-between items-center text-sm mb-1">
                            <span class="font-medium text-blue-900">Selected Size:</span>
                            <span id="selectedSizeDisplay" class="text-blue-700 font-semibold">
                                {{ ($product->available_sizes && count($product->available_sizes) > 0) ? $product->available_sizes[0] : '' }}
                            </span>
                        </div>
                        <!-- ⭐ LIMITED STOCK WARNING - Only show when stock = 1 -->
                        <div id="limitedStockWarning" class="hidden">
                            <div class="text-xs text-orange-600 font-medium mt-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Limited Stock - Only 1 left!
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Available Colors -->
            @if($product->available_colors && is_array($product->available_colors) && count($product->available_colors) > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Available Colors:</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->available_colors as $color)
                            <span class="px-3 py-1 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50">
                                {{ ucfirst($color) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif



            <!-- Short Description -->
            @if($product->short_description)
                <div class="prose prose-gray">
                    <p class="text-gray-600 leading-relaxed">{{ $product->short_description }}</p>
                </div>
            @endif

            <!-- ⭐ ENHANCED: Add to Cart with Size Support -->
            <form id="addToCartForm" action="{{ route('cart.add') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}" id="selectedProductId">
                <input type="hidden" name="size" value="" id="selectedSizeValue">
                
                <div class="flex items-center space-x-4">
                    <label for="quantity" class="text-sm font-medium text-gray-700">Quantity:</label>
                    <input type="number" name="quantity" id="quantity" 
                           min="1" max="{{ $product->stock_quantity ?? 1 }}" value="1"
                           class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm text-gray-500" id="maxQuantityText">Max: {{ $product->stock_quantity ?? 0 }}</span>
                </div>

                <div class="flex space-x-4">
                    @if(($product->stock_quantity ?? 0) > 0)
                        <button type="submit" 
                                id="addToCartBtn"
                                class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Add to Cart
                        </button>
                    @else
                        <button type="button" 
                                disabled
                                id="addToCartBtn"
                                class="flex-1 bg-gray-400 text-white py-3 px-6 rounded-lg cursor-not-allowed flex items-center justify-center">
                            <i class="fas fa-times mr-2"></i>
                            Currently Out of Stock
                        </button>
                    @endif
                    
                    <button type="button" 
                            class="bg-gray-200 text-gray-800 py-3 px-6 rounded-lg hover:bg-gray-300 transition-colors flex items-center justify-center wishlist-btn"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $cleanProductName }}"
                            title="Add to wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </form>

            <!-- Features -->
            @if($product->features && is_array($product->features) && count($product->features) > 0)
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Features</h3>
                    <ul class="space-y-2">
                        @foreach($product->features as $feature)
                            <li class="flex items-start text-gray-600">
                                <i class="fas fa-check text-green-500 mr-3 mt-1 flex-shrink-0"></i>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Specifications -->
            @if($product->weight || $product->length || $product->width || $product->height)
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Specifications</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        @if($product->weight)
                            <div>
                                <span class="font-medium text-gray-600">Weight:</span>
                                <span class="text-gray-900">{{ $product->weight }} kg</span>
                            </div>
                        @endif
                        @if($product->length || $product->width || $product->height)
                            <div>
                                <span class="font-medium text-gray-600">Dimensions:</span>
                                <span class="text-gray-900">
                                    {{ $product->length ?? '-' }} × {{ $product->width ?? '-' }} × {{ $product->height ?? '-' }} cm
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Description -->
    @if($product->description)
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Product Description</h2>
            <div class="prose prose-gray max-w-none bg-gray-50 rounded-lg p-6">
                {!! nl2br(e($product->description)) !!}
            </div>
        </div>
    @endif

    <!-- Related Products -->
    @if(isset($relatedProducts) && $relatedProducts->count() > 0)
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Related Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $relatedProduct)
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative aspect-square">
                            <a href="{{ route('products.show', $relatedProduct->slug) }}">
                                @if($relatedProduct->images && count($relatedProduct->images) > 0)
                                    @php
                                        $relatedImage = filter_var($relatedProduct->images[0], FILTER_VALIDATE_URL) 
                                            ? $relatedProduct->images[0] 
                                            : asset('storage/' . ltrim($relatedProduct->images[0], '/'));
                                    @endphp
                                    <img src="{{ $relatedImage }}" 
                                         alt="{{ $relatedProduct->name }}"
                                         class="w-full h-full object-cover"
                                         onerror="this.src='{{ asset('images/default-product.jpg') }}'">
                                @else
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-shoe-prints text-3xl text-gray-400"></i>
                                    </div>
                                @endif
                            </a>
                        </div>
                        
                        <div class="p-4">
                            @php
                                $relatedCleanName = $relatedProduct->name;
                                if (!empty($relatedProduct->sku_parent)) {
                                    $relatedCleanName = preg_replace('/\s*-\s*' . preg_quote($relatedProduct->sku_parent, '/') . '\s*/', '', $relatedCleanName);
                                    $relatedCleanName = preg_replace('/\s*-\s*Size\s+[A-Z0-9.]+\s*$/i', '', $relatedCleanName);
                                    $relatedCleanName = preg_replace('/\s*Size\s+[A-Z0-9.]+\s*$/i', '', $relatedCleanName);
                                    $relatedCleanName = trim($relatedCleanName, ' -');
                                }
                            @endphp
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ $relatedCleanName }}</h3>
                            <div class="flex items-center justify-between">
                                <div>
                                    @if($relatedProduct->sale_price && $relatedProduct->sale_price < $relatedProduct->price)
                                        <span class="text-lg font-bold text-red-600">
                                            Rp {{ number_format($relatedProduct->sale_price, 0, ',', '.') }}
                                        </span>
                                        <div class="text-sm text-gray-500 line-through">
                                            Rp {{ number_format($relatedProduct->price, 0, ',', '.') }}
                                        </div>
                                    @else
                                        <span class="text-lg font-bold text-gray-900">
                                            Rp {{ number_format($relatedProduct->price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
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
                <button type="button" onclick="hideToast()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🛍️ Product detail page loaded with clean product names');
    
    // Get CSRF token
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    let selectedProductId = '{{ $product->id }}';
    let selectedSize = '{{ ($product->available_sizes && count($product->available_sizes) > 0) ? $product->available_sizes[0] : "" }}';
    
    // ⭐ Size selection functionality
    window.selectSize = function(element) {
        console.log('📏 Size selected:', element.dataset.size);
        
        // Remove previous selection
        document.querySelectorAll('.size-option').forEach(opt => {
            opt.classList.remove('border-blue-500', 'bg-blue-50');
            opt.classList.add('border-gray-300');
        });
        
        // Select current size
        element.classList.remove('border-gray-300');
        element.classList.add('border-blue-500', 'bg-blue-50');
        
        // Update form data
        selectedProductId = element.dataset.productId;
        selectedSize = element.dataset.size;
        const stock = parseInt(element.dataset.stock);
        const price = parseFloat(element.dataset.price);
        const originalPrice = parseFloat(element.dataset.originalPrice);
        
        // Update hidden form fields
        document.getElementById('selectedProductId').value = selectedProductId;
        document.getElementById('selectedSizeValue').value = selectedSize;
        
        // Update size display
        document.getElementById('selectedSizeDisplay').textContent = selectedSize;
        document.getElementById('selectedSizeInfo').classList.remove('hidden');
        
        // ⭐ Show/hide limited stock warning - ONLY when stock = 1
        const limitedStockWarning = document.getElementById('limitedStockWarning');
        if (limitedStockWarning) {
            if (stock === 1) {
                limitedStockWarning.classList.remove('hidden');
            } else {
                limitedStockWarning.classList.add('hidden');
            }
        }
        
        // Update price display
        updatePriceDisplay(price, originalPrice);
        
        // Update quantity input max
        const quantityInput = document.getElementById('quantity');
        quantityInput.max = stock;
        if (parseInt(quantityInput.value) > stock) {
            quantityInput.value = Math.min(stock, 1);
        }
        
        // Update max quantity text
        document.getElementById('maxQuantityText').textContent = 'Max: ' + stock;
        
        // Update add to cart button
        updateAddToCartButton(stock);
        
        console.log('✅ Size updated:', {
            productId: selectedProductId,
            size: selectedSize,
            stock: stock,
            price: price
        });
    };
    
    // ⭐ Update price display
    function updatePriceDisplay(price, originalPrice) {
        const currentPriceEl = document.getElementById('currentPrice');
        const originalPriceEl = document.getElementById('originalPrice');
        
        if (currentPriceEl) {
            currentPriceEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
        }
        
        if (originalPriceEl && price < originalPrice) {
            originalPriceEl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(originalPrice);
            originalPriceEl.style.display = 'inline';
        } else if (originalPriceEl) {
            originalPriceEl.style.display = 'none';
        }
    }
    
    // ⭐ Update add to cart button
    function updateAddToCartButton(stock) {
        const addToCartBtn = document.getElementById('addToCartBtn');
        
        if (stock > 0) {
            addToCartBtn.disabled = false;
            addToCartBtn.className = 'flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center';
            addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart mr-2"></i>Add to Cart';
        } else {
            addToCartBtn.disabled = true;
            addToCartBtn.className = 'flex-1 bg-gray-400 text-white py-3 px-6 rounded-lg cursor-not-allowed flex items-center justify-center';
            addToCartBtn.innerHTML = '<i class="fas fa-times mr-2"></i>Out of Stock';
        }
    }
    
    // ⭐ Image gallery functionality
    window.changeMainImage = function(imageUrl, element) {
        const mainImage = document.getElementById('mainImage');
        if (mainImage) {
            mainImage.src = imageUrl;
        }
        
        // Update thumbnail selection
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('ring-2', 'ring-blue-500');
        });
        element.classList.add('ring-2', 'ring-blue-500');
    };
    
    // ⭐ Add to cart form submission
    document.getElementById('addToCartForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = document.getElementById('addToCartBtn');
        const originalText = button.innerHTML;
        
        console.log('🛒 Adding to cart:', {
            product_id: formData.get('product_id'),
            size: formData.get('size'),
            quantity: formData.get('quantity')
        });
        
        // Show loading
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
        button.disabled = true;
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('📡 Response status:', response.status);
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
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
        .then(data => {
            console.log('✅ Add to cart response:', data);
            
            if (data.success) {
                button.innerHTML = '<i class="fas fa-check mr-2"></i>Added!';
                button.style.backgroundColor = '#10b981';
                
                showToast(data.message || 'Product added to cart successfully!', 'success');
                
                // Update cart counter if available
                if (data.cart_count !== undefined) {
                    updateCartCounter(data.cart_count);
                }
                
                // Reset button after delay
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    button.style.backgroundColor = '';
                }, 2000);
            } else {
                button.innerHTML = '<i class="fas fa-exclamation mr-2"></i>Failed';
                showToast(data.message || 'Failed to add product to cart', 'error');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            }
        })
        .catch(error => {
            console.error('💥 Add to cart error:', error);
            button.innerHTML = '<i class="fas fa-exclamation mr-2"></i>Error';
            
            // More specific error messages
            if (error.message.includes('HTML instead of JSON')) {
                showToast('Server error - please check if cart route exists', 'error');
            } else if (error.message.includes('Failed to fetch')) {
                showToast('Network error - please check your connection', 'error');
            } else {
                showToast('Error: ' + error.message, 'error');
            }
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        });
    });
    
    // ⭐ Wishlist functionality
    // ⭐ Wishlist functionality (REAL)
document.addEventListener('click', function (ev) {
    const btn = ev.target.closest('.wishlist-btn');
    if (!btn) return;

    // jaga-jaga kalau tombol berada di dalam link/form lain
    ev.preventDefault();
    ev.stopPropagation();

    const productId = btn.dataset.productId;
    const productName = btn.dataset.productName || 'Product';
    const icon = btn.querySelector('i');

    if (!productId) return;

    btn.disabled = true;

    fetch(`/wishlist/toggle/${productId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,            // token sudah kamu ambil di awal script
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(data => {
        // kalau backend mengembalikan redirect (mis. belum login)
        if (data && data.redirect) {
            window.location.href = data.redirect;
            return;
        }

        if (!data || data.success === false) {
            showToast((data && data.message) || 'Gagal mengubah wishlist', 'error');
            return;
        }

        const added = !!data.is_added;

        // Update ikon hati
        if (icon) {
            icon.classList.toggle('fas', added);
            icon.classList.toggle('far', !added);
            icon.style.color = added ? '#ef4444' : '';
        }

        // Update badge jumlah wishlist di header (kalau backend mengirim count)
        if ('wishlist_count' in data) {
            const counterEls = document.querySelectorAll('[data-wishlist-count], .wishlist-badge');
            counterEls.forEach(el => el.textContent = data.wishlist_count);
        }

        showToast(
            `${productName} ${added ? 'added to' : 'removed from'} wishlist`,
            added ? 'success' : 'info'
        );
    })
    .catch(() => {
        showToast('Terjadi kesalahan saat toggle wishlist.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
    });
});

    
    // ⭐ Utility functions
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        const icon = document.getElementById('toastIcon');
        const messageEl = document.getElementById('toastMessage');
        
        if (!toast || !icon || !messageEl) return;
        
        messageEl.textContent = message;
        
        // Set icon based on type
        icon.className = 'fas ';
        switch(type) {
            case 'success':
                icon.className += 'fa-check-circle text-green-500';
                break;
            case 'error':
                icon.className += 'fa-exclamation-circle text-red-500';
                break;
            case 'info':
                icon.className += 'fa-info-circle text-blue-500';
                break;
            default:
                icon.className += 'fa-check-circle text-green-500';
        }
        
        toast.classList.remove('hidden');
        setTimeout(() => hideToast(), 3000);
    }
    
    window.hideToast = function() {
        const toast = document.getElementById('toastNotification');
        if (toast) {
            toast.classList.add('hidden');
        }
    };
    
    function updateCartCounter(count) {
        const cartCounters = document.querySelectorAll('.cart-counter, [data-cart-count], .cart-badge');
        cartCounters.forEach(counter => {
            counter.textContent = count;
            if (count > 0) {
                counter.style.display = 'inline';
            } else {
                counter.style.display = 'none';
            }
        });
        console.log('🔢 Cart counter updated to:', count);
    }
    
    // ⭐ Initialize default size selection
    const firstAvailableSize = document.querySelector('.size-option:not([disabled])');
    if (firstAvailableSize) {
        selectSize(firstAvailableSize);
    }
    
    console.log('✅ Product detail JavaScript initialized with clean product names');
});
</script>

<style>
/* Product detail specific styles */
.size-option {
    transition: all 0.2s ease;
    min-width: 60px;
}

.size-option:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.size-option.selected,
.size-option:hover:not(:disabled) {
    border-color: #3b82f6 !important;
    background-color: #eff6ff !important;
}

.thumbnail {
    transition: all 0.2s ease;
}

.thumbnail:hover {
    transform: scale(1.02);
}

#toastNotification {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .size-option {
        min-width: 50px;
        padding: 10px 12px;
    }
    
    .size-option .font-semibold {
        font-size: 14px;
    }
}

/* Line clamp utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Color classes */
.text-green-600 { color: #059669; }
.text-red-600 { color: #dc2626; }
.text-blue-600 { color: #2563eb; }
.text-blue-700 { color: #1d4ed8; }
.text-blue-900 { color: #1e3a8a; }
.text-yellow-600 { color: #d97706; }
.text-orange-600 { color: #ea580c; }
</style>
@endsection