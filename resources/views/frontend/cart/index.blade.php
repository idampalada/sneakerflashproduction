{{-- File: resources/views/frontend/cart/index.blade.php - NO TAX VERSION --}}
@extends('layouts.app')

@section('title', 'Shopping Cart - SneakerFlash')

@section('content')
<!-- Page Header -->
<section class="bg-white py-6 border-b border-gray-200">
    <div class="container mx-auto px-4">
        <nav class="text-sm mb-4">
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
                            // Clean product name - remove SKU parent pattern AND size patterns
                            $originalName = $item['name'] ?? 'Unknown Product';
                            $skuParent = $item['sku_parent'] ?? '';
                            
                            $cleanProductName = $originalName;
                            if (!empty($skuParent)) {
                                $cleanProductName = preg_replace('/\s*-\s*' . preg_quote($skuParent, '/') . '\s*/', '', $cleanProductName);
                                $cleanProductName = preg_replace('/\s*' . preg_quote($skuParent, '/') . '\s*/', '', $cleanProductName);
                            }
                            
                            // Remove size patterns
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
                            
                            // Image URL with proper fallback
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
                        
                        <div class="p-6 border-b border-gray-200 last:border-b-0 cart-item" 
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
                                    
                                    <!-- Size Display -->
                                    @if($hasValidSize)
                                        <div class="mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-ruler-combined mr-1"></i>
                                                Size: {{ $productSize }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    <!-- SKU Display -->
                                    @if(!empty($item['sku']))
                                        <p class="text-xs text-gray-400 mb-2">
                                            SKU: {{ $item['sku'] }}
                                        </p>
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
                                        <!-- DECREASE BUTTON -->
                                        <button type="button" 
                                                onclick="decreaseQuantity('{{ $item['id'] ?? '' }}')"
                                                class="decrease-btn w-10 h-10 bg-gray-50 hover:bg-gray-100 flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                {{ $currentQuantity <= 1 ? 'disabled' : '' }}
                                                data-product-id="{{ $item['id'] ?? '' }}">
                                            <i class="fas fa-minus text-xs text-gray-600"></i>
                                        </button>
                                        
                                        <!-- QUANTITY INPUT -->
                                        <input type="number" 
                                               id="quantity-{{ $item['id'] ?? '' }}"
                                               value="{{ $currentQuantity }}" 
                                               min="1"
                                               max="{{ $stockQuantity }}"
                                               class="quantity-input w-16 h-10 text-center border-0 focus:outline-none text-sm font-medium"
                                               data-product-id="{{ $item['id'] ?? '' }}"
                                               data-cart-key="{{ $cartKey }}"
                                               data-original-value="{{ $currentQuantity }}">
                                        
                                        <!-- INCREASE BUTTON -->
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
                                            Rp {{ number_format(($item['subtotal'] ?? 0), 0, ',', '.') }}
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
                    @endforeach
                </div>

                <!-- Cart Actions -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0 bg-white rounded-2xl p-6 border border-gray-100">
                    <a href="{{ route('products.index') }}" class="flex items-center text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                    </a>
                    
                    <div class="flex space-x-4">
                        <button type="button"
                                onclick="syncCart()"
                                class="flex items-center text-gray-600 hover:text-gray-800 font-medium transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Update Cart
                        </button>
                        
                        <button type="button"
                                onclick="clearCart()"
                                class="flex items-center text-red-600 hover:text-red-800 font-medium transition-colors">
                            <i class="fas fa-trash mr-2"></i>Clear Cart
                        </button>
                    </div>
                </div>
            </div>

            <!-- Order Summary - NO TAX VERSION -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-md p-6 sticky top-4 border border-gray-100">
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
                        
                        <!-- REMOVED TAX DISPLAY -->
                        
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

                    <!-- Security Badge -->
                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-500 flex items-center justify-center">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Secure checkout with SSL encryption
                        </p>
                    </div>

                    <!-- Payment Methods -->
                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-500 mb-2">We accept:</p>
                        <div class="flex justify-center space-x-2">
                            <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                            <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
                            <i class="fas fa-mobile-alt text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Empty Cart -->
        <div class="text-center py-16">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🛒 Cart page loaded - NO TAX VERSION');
    
    // Get CSRF token
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        console.error('❌ CSRF token not found');
        return;
    }

    // Update cart function
    window.updateCart = function(productId, quantity) {
        console.log('📝 Updating cart:', productId, 'quantity:', quantity);
        
        const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
        if (!cartItem) {
            console.error('❌ Cart item not found');
            return;
        }
        
        const cartKey = cartItem.getAttribute('data-cart-key') || productId;
        console.log('🔑 Using cart key:', cartKey);
        
        // Show loading
        cartItem.style.opacity = '0.6';
        cartItem.style.pointerEvents = 'none';

        fetch(`/cart/update/${encodeURIComponent(cartKey)}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({ quantity: quantity })
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Update response:', data);
            
            if (data.success) {
                // Update quantity input
                const quantityInput = document.getElementById(`quantity-${productId}`);
                if (quantityInput) {
                    quantityInput.value = quantity;
                    quantityInput.setAttribute('data-original-value', quantity);
                }
                
                // Update subtotal
                const subtotalEl = document.getElementById(`subtotal-${productId}`);
                if (subtotalEl && data.subtotal) {
                    subtotalEl.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(data.subtotal)}`;
                }
                
                // Update button states
                const decreaseBtn = cartItem.querySelector('.decrease-btn');
                const increaseBtn = cartItem.querySelector('.increase-btn');
                if (decreaseBtn) decreaseBtn.disabled = quantity <= 1;
                if (increaseBtn && data.stock) increaseBtn.disabled = quantity >= data.stock;
                
                // Update totals WITHOUT TAX
                if (data.total) {
                    updateTotals(data.total);
                }
                
                showToast('Cart updated successfully', 'success');
            } else {
                showToast(data.message || 'Failed to update cart', 'error');
                // Revert quantity
                const input = document.getElementById(`quantity-${productId}`);
                if (input) {
                    input.value = input.getAttribute('data-original-value') || '1';
                }
            }
        })
        .catch(error => {
            console.error('💥 Update error:', error);
            showToast('Network error. Please try again.', 'error');
            // Revert quantity
            const input = document.getElementById(`quantity-${productId}`);
            if (input) {
                input.value = input.getAttribute('data-original-value') || '1';
            }
        })
        .finally(() => {
            // Remove loading
            cartItem.style.opacity = '';
            cartItem.style.pointerEvents = '';
        });
    };

    // Increase quantity
    window.increaseQuantity = function(productId) {
        const input = document.getElementById(`quantity-${productId}`);
        if (!input) return;
        
        const current = parseInt(input.value) || 1;
        const max = parseInt(input.getAttribute('max')) || 999;
        
        if (current < max) {
            updateCart(productId, current + 1);
        } else {
            showToast('Maximum stock reached', 'info');
        }
    };

    // Decrease quantity
    window.decreaseQuantity = function(productId) {
        const input = document.getElementById(`quantity-${productId}`);
        if (!input) return;
        
        const current = parseInt(input.value) || 1;
        
        if (current > 1) {
            updateCart(productId, current - 1);
        } else {
            showToast('Minimum quantity is 1', 'info');
        }
    };

    // Remove from cart
    window.removeFromCart = function(productId, productName) {
        if (!confirm(`Remove "${productName}" from cart?`)) {
            return;
        }

        const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
        const cartKey = cartItem ? cartItem.getAttribute('data-cart-key') : productId;
        
        if (cartItem) {
            cartItem.style.opacity = '0.5';
            cartItem.style.transform = 'scale(0.95)';
        }

        fetch(`/cart/remove/${encodeURIComponent(cartKey)}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (cartItem) {
                    cartItem.remove();
                }
                
                // Check if cart is empty
                const remainingItems = document.querySelectorAll('[data-product-id]').length;
                if (remainingItems === 0) {
                    setTimeout(() => location.reload(), 500);
                }
                
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Failed to remove item', 'error');
                if (cartItem) {
                    cartItem.style.opacity = '';
                    cartItem.style.transform = '';
                }
            }
        })
        .catch(error => {
            console.error('💥 Remove error:', error);
            showToast('Network error. Please try again.', 'error');
            if (cartItem) {
                cartItem.style.opacity = '';
                cartItem.style.transform = '';
            }
        });
    };

    // Clear cart
    window.clearCart = function() {
        if (!confirm('Are you sure you want to clear all items from your cart?')) {
            return;
        }

        fetch('/cart/clear', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Cart cleared successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to clear cart', 'error');
            }
        })
        .catch(error => {
            console.error('💥 Clear error:', error);
            showToast('Network error. Please try again.', 'error');
        });
    };

    // Sync cart
    window.syncCart = function() {
        fetch('/cart/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.updated) {
                    showToast('Cart updated with latest data!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Cart is already up to date', 'info');
                }
            } else {
                showToast('Failed to sync cart', 'error');
            }
        })
        .catch(error => {
            console.error('💥 Sync error:', error);
            showToast('Network error. Please try again.', 'error');
        });
    };

    // Proceed to checkout
    window.proceedToCheckout = function() {
        // Check for out of stock items
        const outOfStockItems = document.querySelectorAll('[data-product-id]').length;
        if (outOfStockItems === 0) {
            showToast('Your cart is empty', 'error');
            return;
        }
        
        window.location.href = '{{ route("checkout.index") }}';
    };

    // Helper functions
    function updateTotals(total) {
        // NO TAX VERSION - only update subtotal and final total (no tax calculation)
        const formatted = `Rp ${new Intl.NumberFormat('id-ID').format(total)}`;
        const cartTotal = document.getElementById('cartTotal');
        const finalTotal = document.getElementById('finalTotal');
        
        if (cartTotal) cartTotal.textContent = formatted;
        if (finalTotal) finalTotal.textContent = formatted;
        
        console.log('💰 Updated totals (NO TAX):', { total, formatted });
    }

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

    // Handle quantity input changes
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const quantity = parseInt(this.value) || 1;
            const originalValue = parseInt(this.getAttribute('data-original-value')) || 1;
            
            if (quantity !== originalValue && quantity >= 1) {
                this.setAttribute('data-original-value', quantity);
                updateCart(productId, quantity);
            }
        });
    });

    console.log('✅ Cart JavaScript initialized - NO TAX VERSION');
});
</script>

<style>
/* Cart specific styles */
.cart-item {
    transition: all 0.3s ease;
}

.quantity-input:focus {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
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

/* Button states */
.decrease-btn:disabled,
.increase-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.decrease-btn:not(:disabled):hover,
.increase-btn:not(:disabled):hover {
    background-color: #f3f4f6;
}

/* Error fallback for images */
img[src=""], img:not([src]) {
    display: none;
}
</style>
@endsection