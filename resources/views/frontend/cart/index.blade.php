{{-- File: resources/views/frontend/cart/index.blade.php - MOBILE OPTIMIZED HTML --}}
@extends('layouts.app')

@section('title', 'Shopping Cart - SneakerFlash')

@section('content')
<!-- Page Header -->
<section class="bg-white py-6 border-b border-gray-200 mobile-page-header">
    <div class="container mx-auto px-4">
        <nav class="text-sm mb-4 mobile-breadcrumb">
            <ol class="flex space-x-2 text-gray-600">
                <li><a href="/" class="hover:text-blue-600">Home</a></li>
                <li>/</li>
                <li class="text-gray-900">Shopping Cart</li>
            </ol>
        </nav>
        
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
            <div class="text-gray-600">
                {{ isset($cartItems) ? $cartItems->count() : 0 }} items
            </div>
        </div>
    </div>
</section>

<div class="container mx-auto px-4 py-8">
    @if(isset($cartItems) && $cartItems->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-gray-100">
                    @foreach($cartItems as $item)
                        @php
                            // Clean product name
                            $originalName = $item['name'] ?? 'Unknown Product';
                            $skuParent = $item['sku_parent'] ?? '';
                            
                            $cleanProductName = $originalName;
                            if (!empty($skuParent)) {
                                $cleanProductName = preg_replace('/\s*-\s*' . preg_quote($skuParent, '/') . '\s*/', '', $cleanProductName);
                                $cleanProductName = preg_replace('/\s*' . preg_quote($skuParent, '/') . '\s*/', '', $cleanProductName);
                            }
                            
                            $cleanProductName = preg_replace('/\s*-\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanProductName);
                            $cleanProductName = preg_replace('/\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanProductName);
                            $cleanProductName = preg_replace('/\s*-\s*(XS|S|M|L|XL|XXL|XXXL|[0-9]+|[0-9]+\.[0-9]+)\s*$/i', '', $cleanProductName);
                            $cleanProductName = trim($cleanProductName, ' -');
                            
                            // Size information
                            $productSize = null;
                            if (isset($item['size']) && !empty($item['size'])) {
                                $productSize = is_array($item['size']) ? ($item['size'][0] ?? null) : $item['size'];
                            } elseif (isset($item['product_options']['size'])) {
                                $productSize = $item['product_options']['size'];
                            }
                            
                            $hasValidSize = !empty($productSize) && 
                                          $productSize !== 'One Size' && 
                                          $productSize !== 'Default' &&
                                          !is_array($productSize);
                            
                            // Cart key
                            $cartKey = $item['cart_key'] ?? $item['id'] ?? 'unknown';
                            
                            // Price information
                            $currentPrice = $item['price'] ?? 0;
                            $originalPrice = $item['original_price'] ?? $currentPrice;
                            $stockQuantity = $item['stock'] ?? 0;
                            $currentQuantity = $item['quantity'] ?? 1;
                            $subtotal = $currentPrice * $currentQuantity;
                            
                            // Image URL
                            $imageUrl = asset('images/default-product.jpg');
                            if (!empty($item['image'])) {
                                $imagePath = $item['image'];
                                if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                                    $imageUrl = $imagePath;
                                } elseif (str_starts_with($imagePath, '/storage/')) {
                                    $imageUrl = config('app.url') . $imagePath;
                                } elseif (str_starts_with($imagePath, 'products/')) {
                                    $imageUrl = config('app.url') . '/storage/' . $imagePath;
                                } elseif (str_starts_with($imagePath, 'assets/') || str_starts_with($imagePath, 'images/')) {
                                    $imageUrl = asset($imagePath);
                                } else {
                                    $imageUrl = config('app.url') . '/storage/products/' . $imagePath;
                                }
                            }
                        @endphp
                        
                        <!-- Desktop Layout (Hidden on Mobile) -->
                        <div class="hidden md:block p-6 border-b border-gray-200 last:border-b-0 cart-item" 
                             data-product-id="{{ $item['id'] ?? '' }}" 
                             data-cart-key="{{ $cartKey }}">
                            
                            <div class="flex items-center space-x-4">
                                <!-- Product Image -->
                                <div class="flex-shrink-0">
                                    <img src="{{ $imageUrl }}" 
                                         alt="{{ $cleanProductName }}"
                                         class="w-24 h-24 object-cover rounded-xl"
                                         onerror="this.src='{{ asset('images/default-product.jpg') }}'">
                                </div>

                                <!-- Product Info -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 text-lg mb-1">{{ $cleanProductName }}</h3>
                                    
                                    @if(!empty($item['brand']))
                                        <p class="text-sm text-gray-500 mb-1">{{ $item['brand'] }}</p>
                                    @endif
                                    
                                    @if(!empty($item['category']))
                                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">{{ $item['category'] }}</p>
                                    @endif
                                    
                                    @if($hasValidSize)
                                        <div class="mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-ruler-combined mr-1"></i>
                                                Size: {{ $productSize }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    @if(!empty($item['sku']))
                                        <p class="text-xs text-gray-400 mb-2">SKU: {{ $item['sku'] }}</p>
                                    @endif
                                    
                                    <!-- Price Display -->
                                    <div class="flex items-center space-x-2 mb-2">
                                        @if($currentPrice < $originalPrice)
                                            <span class="text-lg font-bold text-red-600">
                                                Rp {{ number_format($currentPrice, 0, ',', '.') }}
                                            </span>
                                            <span class="text-sm text-gray-400 line-through">
                                                Rp {{ number_format($originalPrice, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-lg font-bold text-gray-900">
                                                Rp {{ number_format($currentPrice, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Stock Status -->
                                    @if($stockQuantity > 0)
                                        <p class="text-xs text-green-600">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            In Stock ({{ $stockQuantity }} left)
                                        </p>
                                    @else
                                        <p class="text-xs text-red-600">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Out of Stock
                                        </p>
                                    @endif
                                </div>

                                <!-- Quantity Controls -->
                                <div class="flex flex-col items-center space-y-4">
                                    <div class="flex items-center space-x-0 border border-gray-200 rounded-lg overflow-hidden">
                                        <button type="button" 
                                                onclick="decreaseQuantity('{{ $item['id'] ?? '' }}')"
                                                class="decrease-btn w-10 h-10 bg-gray-50 hover:bg-gray-100 flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                {{ $currentQuantity <= 1 ? 'disabled' : '' }}
                                                data-product-id="{{ $item['id'] ?? '' }}">
                                            <i class="fas fa-minus text-xs text-gray-600"></i>
                                        </button>
                                        
                                        <input type="number" 
                                               id="quantity-{{ $item['id'] ?? '' }}"
                                               value="{{ $currentQuantity }}" 
                                               min="1"
                                               max="{{ $stockQuantity }}"
                                               class="quantity-input w-16 h-10 text-center border-0 focus:outline-none text-sm font-medium"
                                               data-product-id="{{ $item['id'] ?? '' }}"
                                               data-cart-key="{{ $cartKey }}"
                                               data-original-value="{{ $currentQuantity }}">
                                        
                                        <button type="button" 
                                                onclick="increaseQuantity('{{ $item['id'] ?? '' }}')"
                                                class="increase-btn w-10 h-10 bg-gray-50 hover:bg-gray-100 flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                {{ $currentQuantity >= $stockQuantity ? 'disabled' : '' }}
                                                data-product-id="{{ $item['id'] ?? '' }}">
                                            <i class="fas fa-plus text-xs text-gray-600"></i>
                                        </button>
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="text-center">
                                        <p class="text-sm text-gray-500">Subtotal</p>
                                        <p class="font-bold text-gray-900" id="subtotal-{{ $item['id'] ?? '' }}">
                                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Remove Button -->
                                <div class="flex flex-col space-y-2">
                                    @php
                                        $removeItemName = $cleanProductName;
                                        if ($hasValidSize) {
                                            $removeItemName .= " (Size: {$productSize})";
                                        }
                                        $removeItemName = addslashes($removeItemName);
                                    @endphp
                                    
                                    <button type="button" 
                                            onclick="removeFromCart('{{ $item['id'] ?? '' }}', '{{ $removeItemName }}')"
                                            class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-all"
                                            title="Remove from cart">
                                        <i class="fas fa-trash text-lg"></i>
                                    </button>
                                    
                                    @if(!empty($item['slug']))
                                        <a href="{{ route('products.show', $item['slug']) }}" 
                                           class="text-gray-600 hover:text-gray-800 p-2 rounded-lg hover:bg-gray-50 transition-all"
                                           title="View product">
                                            <i class="fas fa-eye text-lg"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Layout (Hidden on Desktop) -->
                        <div class="md:hidden mobile-cart-item" 
                             data-product-id="{{ $item['id'] ?? '' }}" 
                             data-cart-key="{{ $cartKey }}">
                            
                            <div class="mobile-cart-grid">
                                <!-- Product Image -->
                                <div class="flex-shrink-0">
                                    <img src="{{ $imageUrl }}" 
                                         alt="{{ $cleanProductName }}"
                                         class="mobile-cart-image"
                                         onerror="this.src='{{ asset('images/default-product.jpg') }}'">
                                </div>

                                <!-- Product Info -->
                                <div class="flex-1 min-w-0">
                                    @if(!empty($item['brand']))
                                        <div class="mobile-cart-brand">{{ $item['brand'] }}</div>
                                    @endif
                                    
                                    <h3 class="mobile-cart-product-name">{{ $cleanProductName }}</h3>
                                    
                                    @if(!empty($item['category']))
                                        <div class="mobile-cart-category">{{ ucfirst($item['category']) }}</div>
                                    @endif
                                    
                                    @if($hasValidSize)
                                        <div class="mobile-cart-size">Size: {{ $productSize }}</div>
                                    @endif
                                    
                                    <!-- Price -->
                                    <div class="mt-2">
                                        @if($currentPrice < $originalPrice)
                                            <span class="mobile-cart-price">Rp {{ number_format($currentPrice, 0, ',', '.') }}</span>
                                            <span class="mobile-cart-original-price">Rp {{ number_format($originalPrice, 0, ',', '.') }}</span>
                                        @else
                                            <span class="mobile-cart-price">Rp {{ number_format($currentPrice, 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Stock Status -->
                                    @if($stockQuantity > 0)
                                        <p class="mobile-cart-stock text-green-600">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            In Stock ({{ $stockQuantity }} left)
                                        </p>
                                    @else
                                        <p class="mobile-cart-stock text-red-600">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Out of Stock
                                        </p>
                                    @endif
                                    
                                    @if(!empty($item['sku']))
                                        <div class="mobile-cart-sku">SKU: {{ $item['sku'] }}</div>
                                    @endif
                                    
                                    <!-- Quantity Controls -->
                                    <div class="mobile-quantity-container">
                                        <div class="mobile-quantity-controls">
                                            <button type="button" 
                                                    onclick="decreaseQuantity('{{ $item['id'] ?? '' }}')"
                                                    class="mobile-quantity-btn"
                                                    {{ $currentQuantity <= 1 ? 'disabled' : '' }}
                                                    data-product-id="{{ $item['id'] ?? '' }}">
                                                <i class="fas fa-minus text-xs"></i>
                                            </button>
                                            
                                            <input type="number" 
                                                   id="quantity-mobile-{{ $item['id'] ?? '' }}"
                                                   class="mobile-quantity-input"
                                                   value="{{ $currentQuantity }}"
                                                   min="1"
                                                   max="{{ $stockQuantity }}"
                                                   data-product-id="{{ $item['id'] ?? '' }}"
                                                   data-cart-key="{{ $cartKey }}"
                                                   readonly>
                                            
                                            <button type="button" 
                                                    onclick="increaseQuantity('{{ $item['id'] ?? '' }}')"
                                                    class="mobile-quantity-btn"
                                                    {{ $currentQuantity >= $stockQuantity ? 'disabled' : '' }}
                                                    data-product-id="{{ $item['id'] ?? '' }}">
                                                <i class="fas fa-plus text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="mobile-cart-actions">
                                    <button type="button" 
                                            onclick="removeFromCart('{{ $item['id'] ?? '' }}', '{{ $removeItemName }}')"
                                            class="mobile-delete-btn"
                                            title="Remove item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    
                                    <div class="text-right mt-2">
                                        <div class="mobile-cart-price" id="subtotal-mobile-{{ $item['id'] ?? '' }}">
                                            Rp {{ number_format($subtotal, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <!-- Desktop Summary -->
                <div class="hidden md:block bg-white rounded-2xl shadow-md p-6 sticky top-4 border border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Summary</h2>
                    
                    <div class="space-y-4 mb-6">
                        @php
                            $totalItems = $cartItems->count();
                            $totalQuantity = $cartItems->sum('quantity');
                            $totalAmount = $total ?? 0;
                        @endphp
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Items ({{ $totalItems }}):</span>
                            <span class="font-medium">{{ $totalQuantity }} pcs</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-semibold" id="cartTotal">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                        </div>
                        
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Shipping:</span>
                            <span>Calculated at checkout</span>
                        </div>
                        
                        <div class="border-t pt-4">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span id="finalTotal">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button"
                            onclick="proceedToCheckout()"
                            class="w-full bg-black text-white py-4 rounded-xl hover:bg-gray-800 transition-colors font-medium text-center flex items-center justify-center">
                        <i class="fas fa-lock mr-2"></i>Proceed to Checkout
                    </button>

                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-500 flex items-center justify-center">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Secure checkout with SSL encryption
                        </p>
                    </div>

                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-500 mb-2">We accept:</p>
                        <div class="flex justify-center space-x-2">
                            <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                            <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
                            <i class="fas fa-mobile-alt text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Mobile Summary -->
                <div class="md:hidden mobile-cart-summary">
                    <h2 class="mobile-summary-title">Order Summary</h2>
                    
                    <div class="space-y-0">
                        <div class="mobile-summary-row">
                            <span class="mobile-summary-label">Subtotal ({{ $totalItems }} items)</span>
                            <span class="mobile-summary-value">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                        </div>
                        
                        <div class="mobile-summary-row">
                            <span class="mobile-summary-label">Shipping</span>
                            <span class="mobile-summary-value text-green-600">Free</span>
                        </div>
                        
                        <div class="mobile-summary-row">
                            <span class="mobile-summary-label">Total</span>
                            <span class="mobile-summary-value">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    <button type="button" 
                            onclick="proceedToCheckout()"
                            class="mobile-checkout-btn">
                        <i class="fas fa-lock mr-2"></i>
                        Secure Checkout
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="{{ route('products.index') }}" 
                           class="text-sm text-gray-600 hover:text-gray-800 underline">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Empty Cart -->
        <div class="text-center py-16 mobile-empty-cart">
            <div class="max-w-md mx-auto">
                <div class="mb-6">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300"></i>
                </div>
                <h2 class="text-2xl font-semibold text-gray-600 mb-4">Your cart is empty</h2>
                <p class="text-gray-500 mb-8">Looks like you haven't added any items to your cart yet. Start browsing our amazing products!</p>
                <div class="space-y-4">
                    <a href="{{ route('products.index') }}" 
                       class="inline-block bg-black text-white px-8 py-3 rounded-xl hover:bg-gray-800 transition-colors font-medium">
                        <i class="fas fa-search mr-2"></i>Start Shopping
                    </a>
                    <div class="flex justify-center space-x-4 text-sm">
                        <a href="{{ route('products.sale') }}" class="text-red-600 hover:text-red-700 transition-colors">
                            <i class="fas fa-percent mr-1"></i>Sale Items
                        </a>
                        <a href="{{ route('products.index', ['featured' => '1']) }}" class="text-yellow-600 hover:text-yellow-700 transition-colors">
                            <i class="fas fa-star mr-1"></i>Featured Products
                        </a>
                    </div>
                </div>
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
@endsection

<style>
    /* Mobile Cart Styles - Add to your cart/index.blade.php or main CSS file */

/* Mobile Cart Optimizations */
@media (max-width: 767px) {
    
    /* Header mobile optimizations */
    .mobile-page-header {
        padding: 12px 0 !important;
    }
    
    .mobile-breadcrumb {
        font-size: 12px !important;
        margin-bottom: 8px !important;
    }
    
    .mobile-breadcrumb a {
        color: #6b7280 !important;
    }
    
    .mobile-breadcrumb .text-gray-900 {
        color: #1f2937 !important;
        font-weight: 500 !important;
    }
    
    .container.mx-auto.px-4 h1 {
        font-size: 1.75rem !important;
        font-weight: 700 !important;
        margin-bottom: 0.5rem !important;
    }
    
    .container.mx-auto.px-4 .text-gray-600 {
        font-size: 0.875rem !important;
    }
    
    /* Mobile cart item container */
    .mobile-cart-item {
        padding: 12px !important;
        border-bottom: 1px solid #f3f4f6 !important;
        background: white !important;
    }
    
    .mobile-cart-item:last-child {
        border-bottom: none !important;
    }
    
    /* Mobile grid layout */
    .mobile-cart-grid {
        display: grid !important;
        grid-template-columns: 80px 1fr auto !important;
        gap: 12px !important;
        align-items: start !important;
    }
    
    /* Product image mobile */
    .mobile-cart-image {
        width: 80px !important;
        height: 80px !important;
        object-fit: cover !important;
        border-radius: 8px !important;
        border: 1px solid #f3f4f6 !important;
    }
    
    /* Brand name */
    .mobile-cart-brand {
        font-size: 11px !important;
        color: #6b7280 !important;
        text-transform: uppercase !important;
        margin-bottom: 2px !important;
        font-weight: 500 !important;
        letter-spacing: 0.025em !important;
    }
    
    /* Product name */
    .mobile-cart-product-name {
        font-size: 14px !important;
        font-weight: 600 !important;
        line-height: 1.3 !important;
        margin-bottom: 4px !important;
        color: #1f2937 !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
    }
    
    /* Category */
    .mobile-cart-category {
        font-size: 10px !important;
        color: #9ca3af !important;
        margin-bottom: 4px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }
    
    /* Size information */
    .mobile-cart-size {
        font-size: 11px !important;
        color: #374151 !important;
        background: #f3f4f6 !important;
        padding: 2px 6px !important;
        border-radius: 4px !important;
        display: inline-block !important;
        margin-bottom: 4px !important;
        font-weight: 500 !important;
    }
    
    /* Price styling */
    .mobile-cart-price {
        font-size: 15px !important;
        font-weight: 700 !important;
        color: #dc2626 !important;
    }
    
    .mobile-cart-original-price {
        font-size: 12px !important;
        color: #9ca3af !important;
        text-decoration: line-through !important;
        margin-left: 4px !important;
    }
    
    /* Stock status */
    .mobile-cart-stock {
        font-size: 10px !important;
        margin-top: 2px !important;
    }
    
    /* SKU styling */
    .mobile-cart-sku {
        font-size: 9px !important;
        color: #9ca3af !important;
        margin-top: 2px !important;
        font-family: monospace !important;
    }
    
    /* Quantity controls */
    .mobile-quantity-container {
        margin-top: 8px !important;
    }
    
    .mobile-quantity-controls {
        border: 1px solid #e5e7eb !important;
        border-radius: 6px !important;
        overflow: hidden !important;
        display: flex !important;
        width: fit-content !important;
    }
    
    .mobile-quantity-btn {
        width: 32px !important;
        height: 32px !important;
        background: #f9fafb !important;
        border: none !important;
        font-size: 14px !important;
        color: #374151 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.2s ease !important;
    }
    
    .mobile-quantity-btn:hover:not(:disabled) {
        background: #f3f4f6 !important;
    }
    
    .mobile-quantity-btn:disabled {
        background: #f9fafb !important;
        color: #d1d5db !important;
        cursor: not-allowed !important;
    }
    
    .mobile-quantity-input {
        width: 45px !important;
        height: 32px !important;
        text-align: center !important;
        border: none !important;
        background: white !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #374151 !important;
        border-left: 1px solid #e5e7eb !important;
        border-right: 1px solid #e5e7eb !important;
    }
    
    /* Action buttons */
    .mobile-cart-actions {
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
        align-items: flex-end !important;
    }
    
    .mobile-delete-btn {
        width: 32px !important;
        height: 32px !important;
        background: #fef2f2 !important;
        border: 1px solid #fecaca !important;
        border-radius: 6px !important;
        color: #dc2626 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 12px !important;
        transition: all 0.2s ease !important;
    }
    
    .mobile-delete-btn:hover {
        background: #fee2e2 !important;
        border-color: #fca5a5 !important;
    }
    
    /* Summary section mobile */
    .mobile-cart-summary {
        background: white !important;
        border-radius: 12px !important;
        padding: 16px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #f3f4f6 !important;
    }
    
    .mobile-summary-title {
        font-size: 18px !important;
        font-weight: 700 !important;
        color: #1f2937 !important;
        margin-bottom: 12px !important;
    }
    
    .mobile-summary-row {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 8px 0 !important;
        border-bottom: 1px solid #f3f4f6 !important;
        font-size: 14px !important;
    }
    
    .mobile-summary-row:last-child {
        border-bottom: none !important;
        padding-top: 12px !important;
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #1f2937 !important;
    }
    
    .mobile-summary-label {
        color: #6b7280 !important;
    }
    
    .mobile-summary-value {
        color: #1f2937 !important;
        font-weight: 600 !important;
    }
    
    /* Checkout button mobile */
    .mobile-checkout-btn {
        width: 100% !important;
        background: #000000 !important;
        color: white !important;
        padding: 14px 20px !important;
        border-radius: 8px !important;
        font-size: 16px !important;
        font-weight: 600 !important;
        border: none !important;
        margin-top: 16px !important;
        transition: all 0.2s ease !important;
        cursor: pointer !important;
    }
    
    .mobile-checkout-btn:hover {
        background: #1f2937 !important;
        transform: translateY(-1px) !important;
    }
    
    /* Container padding */
    .container.mx-auto.px-4.py-8 {
        padding: 12px 16px !important;
    }
    
    /* Grid layout fix */
    .grid.grid-cols-1.lg\\:grid-cols-3.gap-8 {
        display: block !important;
        gap: 16px !important;
    }
    
    .lg\\:col-span-2 {
        margin-bottom: 16px !important;
    }
    
    /* Card styling */
    .bg-white.rounded-2xl.shadow-md {
        border-radius: 12px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #f3f4f6 !important;
    }
    
    /* Reduce overall spacing */
    .py-8 {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }
    
    .gap-8 {
        gap: 1rem !important;
    }
    
    /* Empty cart state mobile */
    .mobile-empty-cart {
        text-align: center !important;
        padding: 32px 16px !important;
    }
    
    .mobile-empty-cart i {
        font-size: 48px !important;
        color: #d1d5db !important;
        margin-bottom: 16px !important;
    }
    
    .mobile-empty-cart h2 {
        font-size: 18px !important;
        font-weight: 600 !important;
        color: #1f2937 !important;
        margin-bottom: 8px !important;
    }
    
    .mobile-empty-cart p {
        font-size: 14px !important;
        color: #6b7280 !important;
        margin-bottom: 20px !important;
    }
}

/* Text utilities for mobile */
@media (max-width: 767px) {
    .text-3xl {
        font-size: 1.75rem !important;
    }
    
    .text-2xl {
        font-size: 1.5rem !important;
    }
    
    .text-xl {
        font-size: 1.25rem !important;
    }
    
    .text-lg {
        font-size: 1rem !important;
    }
    
    .py-6 {
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
    }
    
    .mb-8 {
        margin-bottom: 1rem !important;
    }
    
    .mb-6 {
        margin-bottom: 0.75rem !important;
    }
    
    .mb-4 {
        margin-bottom: 0.5rem !important;
    }
}

/* General cart styles (for both mobile and desktop) */
.cart-item {
    transition: all 0.3s ease;
}

.cart-item:hover {
    background-color: #fafafa;
}

.quantity-input:focus {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
}

/* Toast notification styles */
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

/* Button states for desktop */
.decrease-btn:disabled,
.increase-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.decrease-btn:not(:disabled):hover,
.increase-btn:not(:disabled):hover {
    background-color: #f3f4f6;
}

/* Image error fallback */
img[src=""], img:not([src]) {
    display: none;
}

/* Loading state */
.cart-item[data-loading="true"] {
    opacity: 0.6;
    pointer-events: none;
}

/* Mobile responsiveness for cart actions */
@media (max-width: 640px) {
    .flex.flex-col.sm\\:flex-row {
        flex-direction: column !important;
        gap: 12px !important;
    }
    
    .flex.space-x-4 {
        flex-direction: column !important;
        gap: 8px !important;
        width: 100% !important;
    }
    
    .flex.space-x-4 button {
        width: 100% !important;
        justify-content: center !important;
    }
}
    </style>


<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🛒 Cart page loaded - Simple Version');
    
    // Get CSRF token
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        console.error('❌ CSRF token not found');
        return;
    }

    // Simple Increase Quantity Function
    window.increaseQuantity = function(productId) {
        console.log('Increasing quantity for:', productId);
        
        // Try mobile input first, then desktop
        let quantityInput = document.getElementById('quantity-mobile-' + productId);
        if (!quantityInput) {
            quantityInput = document.getElementById('quantity-' + productId);
        }
        
        console.log('Found input:', quantityInput);
        
        if (quantityInput) {
            const currentValue = parseInt(quantityInput.value);
            const maxValue = parseInt(quantityInput.getAttribute('max')) || 999;
            
            console.log('Current value:', currentValue, 'Max:', maxValue);
            
            if (currentValue < maxValue) {
                const newValue = currentValue + 1;
                
                // Update mobile input
                const mobileInput = document.getElementById('quantity-mobile-' + productId);
                if (mobileInput) {
                    mobileInput.value = newValue;
                }
                
                // Update desktop input
                const desktopInput = document.getElementById('quantity-' + productId);
                if (desktopInput) {
                    desktopInput.value = newValue;
                }
                
                console.log('Updated to:', newValue);
                updateCartQuantity(productId, newValue);
            } else {
                console.log('Cannot increase above max');
                showToast('Maximum stock reached', 'info');
            }
        } else {
            console.error('No quantity input found for product:', productId);
        }
    };

    // Simple Decrease Quantity Function
    window.decreaseQuantity = function(productId) {
        console.log('Decreasing quantity for:', productId);
        
        // Try mobile input first, then desktop
        let quantityInput = document.getElementById('quantity-mobile-' + productId);
        if (!quantityInput) {
            quantityInput = document.getElementById('quantity-' + productId);
        }
        
        console.log('Found input:', quantityInput);
        
        if (quantityInput) {
            const currentValue = parseInt(quantityInput.value);
            console.log('Current value:', currentValue);
            
            if (currentValue > 1) {
                const newValue = currentValue - 1;
                
                // Update mobile input
                const mobileInput = document.getElementById('quantity-mobile-' + productId);
                if (mobileInput) {
                    mobileInput.value = newValue;
                }
                
                // Update desktop input
                const desktopInput = document.getElementById('quantity-' + productId);
                if (desktopInput) {
                    desktopInput.value = newValue;
                }
                
                console.log('Updated to:', newValue);
                updateCartQuantity(productId, newValue);
            } else {
                console.log('Cannot decrease below 1');
                showToast('Minimum quantity is 1', 'info');
            }
        } else {
            console.error('No quantity input found for product:', productId);
        }
    };

    // Update Cart Quantity with actual API call
    function updateCartQuantity(productId, newQuantity) {
        console.log('Updating cart quantity:', productId, newQuantity);
        
        const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
        const cartKey = cartItem ? cartItem.getAttribute('data-cart-key') : productId;
        
        // Show loading state
        if (cartItem) {
            cartItem.style.opacity = '0.7';
        }
        
        // Make actual API call to update quantity
        fetch('/cart/update/' + encodeURIComponent(cartKey), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ quantity: newQuantity })
        })
        .then(response => {
            console.log('Update response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Update response data:', data);
            
            if (data.success) {
                // Update subtotal display
                updateSubtotal(productId, newQuantity);
                
                // Update cart totals if provided
                if (data.cart_total !== undefined) {
                    updateCartTotals(data.cart_total);
                }
                
                // Update original values
                const desktopInput = document.getElementById('quantity-' + productId);
                if (desktopInput) {
                    desktopInput.setAttribute('data-original-value', newQuantity);
                }
                
                showToast('Cart updated successfully', 'success');
            } else {
                showToast(data.message || 'Failed to update cart', 'error');
                // Revert quantity on error
                revertQuantity(productId);
            }
        })
        .catch(error => {
            console.error('Update error:', error);
            showToast('Network error. Please try again.', 'error');
            // Revert quantity on error
            revertQuantity(productId);
        })
        .finally(() => {
            // Remove loading state
            if (cartItem) {
                cartItem.style.opacity = '';
            }
        });
    }

    // Update subtotal display
    function updateSubtotal(productId, quantity) {
        const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
        if (!cartItem) return;

        // Find price element
        const priceElement = cartItem.querySelector('.text-lg.font-bold, .mobile-cart-price');
        if (!priceElement) return;

        // Extract price (remove non-digits)
        const priceText = priceElement.textContent.replace(/[^\d]/g, '');
        const price = parseInt(priceText);
        
        if (price) {
            const newSubtotal = price * quantity;
            const formattedSubtotal = 'Rp ' + new Intl.NumberFormat('id-ID').format(newSubtotal);
            
            // Update desktop subtotal
            const subtotalElement = document.getElementById('subtotal-' + productId);
            if (subtotalElement) {
                subtotalElement.textContent = formattedSubtotal;
            }
            
            // Update mobile subtotal
            const mobileSubtotalElement = document.getElementById('subtotal-mobile-' + productId);
            if (mobileSubtotalElement) {
                mobileSubtotalElement.textContent = formattedSubtotal;
            }
        }
    }

    // Remove from cart with actual API call
    window.removeFromCart = function(productId, productName) {
        console.log('Removing from cart:', productId, productName);
        
        if (confirm('Remove ' + productName + ' from cart?')) {
            // Find both desktop and mobile cart items
            const desktopCartItem = document.querySelector(`.hidden.md\\:block[data-product-id="${productId}"]`);
            const mobileCartItem = document.querySelector(`.md\\:hidden.mobile-cart-item[data-product-id="${productId}"]`);
            
            // Use whichever exists
            const cartItem = mobileCartItem || desktopCartItem || document.querySelector(`[data-product-id="${productId}"]`);
            const cartKey = cartItem ? cartItem.getAttribute('data-cart-key') : productId;
            
            console.log('Found cart items - Desktop:', desktopCartItem, 'Mobile:', mobileCartItem);
            
            if (cartItem) {
                console.log('Found cart item, removing...');
                
                // Visual feedback for both desktop and mobile
                if (desktopCartItem) {
                    desktopCartItem.style.opacity = '0.5';
                    desktopCartItem.style.transform = 'scale(0.95)';
                    desktopCartItem.style.transition = 'all 0.3s ease';
                }
                
                if (mobileCartItem) {
                    mobileCartItem.style.opacity = '0.5';
                    mobileCartItem.style.transform = 'scale(0.95)';
                    mobileCartItem.style.transition = 'all 0.3s ease';
                }
                
                // Make actual API call to remove item
                fetch('/cart/remove/' + encodeURIComponent(cartKey), {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token
                    }
                })
                .then(response => {
                    console.log('Remove response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Remove response data:', data);
                    
                    if (data.success) {
                        // Remove both desktop and mobile items from DOM
                        if (desktopCartItem) {
                            desktopCartItem.remove();
                            console.log('Desktop item removed from DOM');
                        }
                        
                        if (mobileCartItem) {
                            mobileCartItem.remove();
                            console.log('Mobile item removed from DOM');
                        }
                        
                        showToast(data.message || 'Item removed from cart', 'success');
                        
                        // Check remaining items - check both desktop and mobile
                        const remainingDesktopItems = document.querySelectorAll('.hidden.md\\:block[data-product-id]');
                        const remainingMobileItems = document.querySelectorAll('.md\\:hidden.mobile-cart-item[data-product-id]');
                        const totalRemaining = Math.max(remainingDesktopItems.length, remainingMobileItems.length);
                        
                        console.log('Remaining desktop items:', remainingDesktopItems.length);
                        console.log('Remaining mobile items:', remainingMobileItems.length);
                        console.log('Total remaining:', totalRemaining);
                        
                        if (totalRemaining === 0) {
                            console.log('Cart is empty, reloading page...');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            // Update cart totals if provided
                            if (data.cart_total !== undefined) {
                                updateCartTotals(data.cart_total);
                            }
                        }
                    } else {
                        // API returned error
                        showToast(data.message || 'Failed to remove item', 'error');
                        // Revert visual changes
                        if (desktopCartItem) {
                            desktopCartItem.style.opacity = '';
                            desktopCartItem.style.transform = '';
                        }
                        if (mobileCartItem) {
                            mobileCartItem.style.opacity = '';
                            mobileCartItem.style.transform = '';
                        }
                    }
                })
                .catch(error => {
                    console.error('Remove error:', error);
                    showToast('Network error. Please try again.', 'error');
                    // Revert visual changes
                    if (desktopCartItem) {
                        desktopCartItem.style.opacity = '';
                        desktopCartItem.style.transform = '';
                    }
                    if (mobileCartItem) {
                        mobileCartItem.style.opacity = '';
                        mobileCartItem.style.transform = '';
                    }
                });
            } else {
                console.error('Cart item not found');
                showToast('Error: Item not found', 'error');
            }
        }
    };

    // Proceed to checkout
    window.proceedToCheckout = function() {
        const cartItems = document.querySelectorAll('[data-product-id]');
        if (cartItems.length === 0) {
            showToast('Your cart is empty', 'error');
            return;
        }
        
        // Show loading
        const checkoutBtns = document.querySelectorAll('button[onclick="proceedToCheckout()"]');
        checkoutBtns.forEach(btn => {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            btn.disabled = true;
        });
        
        // Redirect to checkout
        setTimeout(() => {
            window.location.href = '/checkout';
        }, 500);
    };

    // Show toast notification
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        const icon = document.getElementById('toastIcon');
        const messageEl = document.getElementById('toastMessage');
        
        if (!toast || !icon || !messageEl) return;
        
        messageEl.textContent = message;
        
        // Set icon based on type
        switch(type) {
            case 'success':
                icon.className = 'fas fa-check-circle text-green-500';
                break;
            case 'error':
                icon.className = 'fas fa-exclamation-circle text-red-500';
                break;
            case 'info':
                icon.className = 'fas fa-info-circle text-blue-500';
                break;
            default:
                icon.className = 'fas fa-check-circle text-green-500';
        }
        
        toast.classList.remove('hidden');
        
        // Auto hide after 3 seconds
        setTimeout(() => hideToast(), 3000);
    }

    // Hide toast
    window.hideToast = function() {
        const toast = document.getElementById('toastNotification');
        if (toast) {
            toast.classList.add('hidden');
        }
    };

    // Revert quantity on error
    function revertQuantity(productId) {
        const quantityInput = document.getElementById('quantity-' + productId);
        const mobileQuantityInput = document.getElementById('quantity-mobile-' + productId);
        
        if (quantityInput) {
            const originalValue = quantityInput.getAttribute('data-original-value') || '1';
            quantityInput.value = originalValue;
            if (mobileQuantityInput) {
                mobileQuantityInput.value = originalValue;
            }
        }
    }

    // Update cart totals
    function updateCartTotals(total) {
        const formatted = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        
        const cartTotal = document.getElementById('cartTotal');
        const finalTotal = document.getElementById('finalTotal');
        
        if (cartTotal) cartTotal.textContent = formatted;
        if (finalTotal) finalTotal.textContent = formatted;
    }

    // Handle quantity input changes manually
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const quantity = parseInt(this.value) || 1;
            const originalValue = parseInt(this.getAttribute('data-original-value')) || 1;
            
            if (quantity !== originalValue && quantity >= 1) {
                updateCartQuantity(productId, quantity);
                this.setAttribute('data-original-value', quantity);
            }
        });
    });

    console.log('✅ Cart JavaScript initialized - Simple Version');
});
</script>