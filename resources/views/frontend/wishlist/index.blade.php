@extends('layouts.app')

@section('title', 'My Wishlist - SneakerFlash')

@section('content')
    <!-- Page Header -->
    <section class="bg-white py-6 border-b border-gray-200">
        <div class="container mx-auto px-4">
            <nav class="text-sm mb-4">
                <ol class="flex space-x-2 text-gray-600">
                    <li><a href="/" class="hover:text-blue-600">Home</a></li>
                    <li>/</li>
                    <li class="text-gray-900">My Wishlist</li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">My Wishlist</h1>
                <div class="text-gray-600">
                    <span id="wishlistItemCount">{{ $wishlists->count() }}</span> items
                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 py-8">
        @if($wishlists->count() > 0)
            <!-- Wishlist Actions Bar -->
            <div class="bg-white rounded-2xl p-6 mb-6 border border-gray-100">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600 font-medium">{{ $wishlists->count() }} products in your wishlist</span>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <button type="button" id="moveAllToCartBtn" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors font-medium">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Move All to Cart
                        </button>
                        <button type="button" id="clearWishlistBtn" class="border border-red-300 text-red-600 px-6 py-2 rounded-lg hover:bg-red-50 transition-colors font-medium">
                            <i class="fas fa-trash mr-2"></i>
                            Clear Wishlist
                        </button>
                    </div>
                </div>
            </div>

            <!-- Wishlist Items Grid -->
            <div id="wishlistGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($wishlists as $wishlist)
                    @if($wishlist->product)
                        <div class="wishlist-item bg-white rounded-2xl overflow-hidden border border-gray-100 hover:shadow-lg transition-all duration-300 group"
                             data-product-id="{{ $wishlist->product->id }}"
                             data-wishlist-id="{{ $wishlist->id }}">
                            <div class="relative aspect-square bg-gray-50 overflow-hidden">
                                @if($wishlist->product->images && count($wishlist->product->images) > 0)
                                    @php
                                        // ✅ FIXED: Handle both URL and storage images
                                        $imagePath = $wishlist->product->images[0];
                                        $imageUrl = '';
                                        
                                        // Case 1: Already a full URL
                                        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                                            $imageUrl = $imagePath;
                                        }
                                        // Case 2: Storage path starting with /storage/
                                        elseif (str_starts_with($imagePath, '/storage/')) {
                                            $imageUrl = config('app.url') . $imagePath;
                                        }
                                        // Case 3: Relative storage path (products/filename.jpg)
                                        elseif (str_starts_with($imagePath, 'products/')) {
                                            $imageUrl = config('app.url') . '/storage/' . $imagePath;
                                        }
                                        // Case 4: Asset path
                                        elseif (str_starts_with($imagePath, 'assets/') || str_starts_with($imagePath, 'images/')) {
                                            $imageUrl = asset($imagePath);
                                        }
                                        // Case 5: Filename only - assume in products folder
                                        elseif (!str_contains($imagePath, '/')) {
                                            $imageUrl = config('app.url') . '/storage/products/' . $imagePath;
                                        }
                                        // Case 6: Generic fallback
                                        else {
                                            $imageUrl = config('app.url') . '/storage/' . ltrim($imagePath, '/');
                                        }
                                    @endphp
                                    <img src="{{ $imageUrl }}" 
                                         alt="{{ $wishlist->product->name }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                         onerror="this.src='{{ asset('images/default-product.png') }}'">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <i class="fas fa-shoe-prints text-4xl text-gray-300"></i>
                                    </div>
                                @endif
                                
                                <!-- Product badges -->
                                <div class="absolute top-3 left-3 flex flex-col gap-2">
                                    @if($wishlist->product->is_featured)
                                        <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                            Featured
                                        </span>
                                    @endif
                                    @if($wishlist->product->sale_price && $wishlist->product->sale_price < $wishlist->product->price)
                                        @php
                                            $discount = round((($wishlist->product->price - $wishlist->product->sale_price) / $wishlist->product->price) * 100);
                                        @endphp
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                            -{{ $discount }}%
                                        </span>
                                    @endif
                                    @if($wishlist->product->stock_quantity <= 0)
                                        <span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                            Out of Stock
                                        </span>
                                    @elseif($wishlist->product->stock_quantity < 10)
                                        <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                            Low Stock
                                        </span>
                                    @endif
                                </div>

                                <!-- Remove from wishlist button -->
                                <button type="button" class="remove-wishlist-btn absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:shadow-lg transition-all duration-200" 
                                        data-product-id="{{ $wishlist->product->id }}"
                                        data-product-name="{{ $wishlist->product->name }}"
                                        title="Remove from wishlist">
                                    <i class="fas fa-times text-gray-600 hover:text-red-500 transition-colors"></i>
                                </button>

                                <!-- Added to wishlist date -->
                                <div class="absolute bottom-3 left-3">
                                    <span class="bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded-full">
                                        Added {{ $wishlist->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <div class="mb-2">
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">
                                        {{ $wishlist->product->category->name ?? 'Products' }}
                                        @if($wishlist->product->brand)
                                            • {{ $wishlist->product->brand }}
                                        @endif
                                    </span>
                                </div>
                                
                                <h3 class="font-semibold text-gray-900 mb-3 text-sm leading-tight">
                                    <a href="{{ route('products.show', $wishlist->product->slug) }}" 
                                       class="hover:text-blue-600 transition-colors">
                                        {{ $wishlist->product->name }}
                                    </a>
                                </h3>
                                
                                <!-- Display available sizes and colors if exist -->
                                @if($wishlist->product->available_sizes || $wishlist->product->available_colors)
                                    <div class="mb-3 text-xs text-gray-500">
                                        @if($wishlist->product->available_sizes)
                                            <div class="mb-1">
                                                <span class="font-medium">Sizes:</span>
                                                {{ is_array($wishlist->product->available_sizes) ? implode(', ', array_slice($wishlist->product->available_sizes, 0, 3)) : $wishlist->product->available_sizes }}
                                                @if(is_array($wishlist->product->available_sizes) && count($wishlist->product->available_sizes) > 3)
                                                    <span class="text-gray-400">+{{ count($wishlist->product->available_sizes) - 3 }} more</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if($wishlist->product->available_colors)
                                            <div>
                                                <span class="font-medium">Colors:</span>
                                                {{ is_array($wishlist->product->available_colors) ? implode(', ', array_slice($wishlist->product->available_colors, 0, 3)) : $wishlist->product->available_colors }}
                                                @if(is_array($wishlist->product->available_colors) && count($wishlist->product->available_colors) > 3)
                                                    <span class="text-gray-400">+{{ count($wishlist->product->available_colors) - 3 }} more</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                <!-- Price -->
                                <div class="mb-4">
                                    @if($wishlist->product->sale_price && $wishlist->product->sale_price < $wishlist->product->price)
                                        <div class="flex items-center space-x-2">
                                            <span class="text-lg font-bold text-red-600">
                                                Rp {{ number_format($wishlist->product->sale_price, 0, ',', '.') }}
                                            </span>
                                            <span class="text-sm text-gray-400 line-through">
                                                Rp {{ number_format($wishlist->product->price, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-lg font-bold text-gray-900">
                                            Rp {{ number_format($wishlist->product->price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Stock Status -->
                                <div class="mb-3">
                                    @if($wishlist->product->stock_quantity > 0)
                                        <span class="text-xs text-green-600 font-medium">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            In Stock ({{ $wishlist->product->stock_quantity }} left)
                                        </span>
                                    @else
                                        <span class="text-xs text-red-600 font-medium">
                                            <i class="fas fa-times-circle mr-1"></i>
                                            Out of Stock
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="flex gap-2">
                                    @if($wishlist->product->stock_quantity > 0)
                                        <button type="button" class="move-to-cart-btn flex-1 bg-green-600 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
                                                data-product-id="{{ $wishlist->product->id }}"
                                                data-product-name="{{ $wishlist->product->name }}">
                                            <i class="fas fa-shopping-cart mr-1"></i>
                                            Move to Cart
                                        </button>
                                    @else
                                        <button type="button" class="flex-1 bg-gray-400 text-white py-2 px-3 rounded-lg text-sm font-medium cursor-not-allowed" disabled>
                                            <i class="fas fa-times mr-1"></i>
                                            Out of Stock
                                        </button>
                                    @endif
                                    <a href="{{ route('products.show', $wishlist->product->slug) }}" class="px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
                                        <i class="fas fa-eye text-gray-600"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Continue Shopping -->
            <div class="text-center mt-8">
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Continue Shopping
                </a>
            </div>

        @else
            <!-- Empty Wishlist State -->
            <div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
                <div class="max-w-md mx-auto">
                    <div class="mb-6">
                        <i class="fas fa-heart text-6xl text-gray-300"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-4">Your wishlist is empty</h3>
                    <p class="text-gray-500 mb-8">
                        Looks like you haven't added any products to your wishlist yet. 
                        Start browsing and add your favorite items!
                    </p>
                    <div class="space-y-4">
                        <a href="{{ route('products.index') }}" class="block bg-black text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition-colors font-medium">
                            <i class="fas fa-search mr-2"></i>
                            Browse Products
                        </a>
                        <div class="flex justify-center space-x-4 text-sm">
                            <a href="{{ route('products.sale') }}" class="text-red-600 hover:text-red-700 transition-colors">
                                <i class="fas fa-percent mr-1"></i>
                                Sale Items
                            </a>
                            <a href="{{ route('products.index', ['featured' => '1']) }}" class="text-yellow-600 hover:text-yellow-700 transition-colors">
                                <i class="fas fa-star mr-1"></i>
                                Featured Products
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

    <!-- Styles -->
    <style>
    /* Wishlist specific styles */
    .wishlist-item {
        transition: all 0.3s ease;
    }

    .wishlist-item:hover {
        transform: translateY(-2px);
    }

    .remove-wishlist-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .move-to-cart-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* Animation for removing items */
    .wishlist-item.removing {
        opacity: 0.5;
        transform: scale(0.95);
        transition: all 0.3s ease;
    }

    /* Toast notification animation */
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

    /* Loading state */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .wishlist-item {
            margin-bottom: 1rem;
        }
        
        .move-to-cart-btn {
            font-size: 12px;
            padding: 8px 12px;
        }
        
        .remove-wishlist-btn {
            width: 32px;
            height: 32px;
            top: 8px;
            right: 8px;
        }
    }
    </style>

    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Wishlist page loaded');
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            return;
        }
        
        const token = csrfToken.getAttribute('content');
        console.log('CSRF token found:', token ? 'Yes' : 'No');
        
        // Initialize all event listeners
        initializeEventListeners();
        
        function initializeEventListeners() {
            // Remove from wishlist buttons
            const removeButtons = document.querySelectorAll('.remove-wishlist-btn');
            console.log('Found remove buttons:', removeButtons.length);
            
            removeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const productId = this.getAttribute('data-product-id');
                    const productName = this.getAttribute('data-product-name');
                    const wishlistItem = this.closest('.wishlist-item');
                    
                    console.log('Remove clicked:', productId, productName);
                    
                    if (productId && wishlistItem) {
                        removeFromWishlist(productId, productName, wishlistItem);
                    }
                });
            });

            // Move to cart buttons
            const moveButtons = document.querySelectorAll('.move-to-cart-btn');
            console.log('Found move buttons:', moveButtons.length);
            
            moveButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const productId = this.getAttribute('data-product-id');
                    const productName = this.getAttribute('data-product-name');
                    const wishlistItem = this.closest('.wishlist-item');
                    
                    console.log('Move to cart clicked:', productId, productName);
                    
                    if (productId && wishlistItem) {
                        moveToCart(productId, productName, wishlistItem);
                    }
                });
            });
            
            // Clear wishlist button
            const clearBtn = document.getElementById('clearWishlistBtn');
            if (clearBtn) {
                clearBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Clear wishlist clicked');
                    clearWishlist();
                });
            }
            
            // Move all to cart button
            const moveAllBtn = document.getElementById('moveAllToCartBtn');
            if (moveAllBtn) {
                moveAllBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Move all to cart clicked');
                    moveAllToCart();
                });
            }
        }

        // Remove from wishlist function
        function removeFromWishlist(productId, productName, wishlistItem) {
            console.log('Removing from wishlist:', productId);
            
            // Show loading state
            const button = wishlistItem.querySelector('.remove-wishlist-btn');
            if (button) {
                button.classList.add('loading');
                button.disabled = true;
            }

            fetch(`/wishlist/toggle/${productId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                }
            })
            .then(response => {
                console.log('Remove response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Remove response data:', data);
                
                if (data.success) {
                    // Remove item with animation
                    wishlistItem.classList.add('removing');
                    
                    setTimeout(() => {
                        wishlistItem.remove();
                        updateWishlistCount();
                        showToast(`${productName} removed from wishlist!`, 'info');
                        
                        // Check if empty
                        const remainingItems = document.querySelectorAll('.wishlist-item').length;
                        if (remainingItems === 0) {
                            setTimeout(() => location.reload(), 1000);
                        }
                    }, 300);
                    
                } else {
                    // Remove loading state on error
                    if (button) {
                        button.classList.remove('loading');
                        button.disabled = false;
                    }
                    showToast(data.message || 'Failed to remove from wishlist', 'error');
                }
            })
            .catch(error => {
                console.error('Remove error:', error);
                // Remove loading state on error
                if (button) {
                    button.classList.remove('loading');
                    button.disabled = false;
                }
                showToast('Something went wrong. Please try again.', 'error');
            });
        }

        // Move to cart function
        function moveToCart(productId, productName, wishlistItem) {
            console.log('Moving to cart:', productId);
            
            // Show loading state
            const button = wishlistItem.querySelector('.move-to-cart-btn');
            if (button) {
                button.classList.add('loading');
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Moving...';
            }

            fetch(`/wishlist/move-to-cart/${productId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                }
            })
            .then(response => {
                console.log('Move response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Move response data:', data);
                
                if (data.success) {
                    // Remove item with animation
                    wishlistItem.classList.add('removing');
                    
                    setTimeout(() => {
                        wishlistItem.remove();
                        updateWishlistCount();
                        updateCartCount(data.cart_count);
                        showToast(`${productName} moved to cart!`, 'success');
                        
                        // Check if empty
                        const remainingItems = document.querySelectorAll('.wishlist-item').length;
                        if (remainingItems === 0) {
                            setTimeout(() => location.reload(), 1000);
                        }
                    }, 300);
                    
                } else {
                    // Remove loading state on error
                    if (button) {
                        button.classList.remove('loading');
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-shopping-cart mr-1"></i>Move to Cart';
                    }
                    showToast(data.message || 'Failed to move to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Move error:', error);
                // Remove loading state on error
                if (button) {
                    button.classList.remove('loading');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-shopping-cart mr-1"></i>Move to Cart';
                }
                showToast('Something went wrong. Please try again.', 'error');
            });
        }

        // Clear wishlist function
        function clearWishlist() {
            if (!confirm('Are you sure you want to clear your entire wishlist?')) {
                return;
            }
            
            console.log('Clearing wishlist');
            
            fetch('/wishlist/clear', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                }
            })
            .then(response => {
                console.log('Clear response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Clear response data:', data);
                
                if (data.success) {
                    showToast('Wishlist cleared successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.message || 'Failed to clear wishlist', 'error');
                }
            })
            .catch(error => {
                console.error('Clear error:', error);
                showToast('Something went wrong. Please try again.', 'error');
            });
        }

        // Move all to cart function
        function moveAllToCart() {
            if (!confirm('Move all available items to cart?')) {
                return;
            }
            
            const availableButtons = document.querySelectorAll('.move-to-cart-btn:not(:disabled)');
            if (availableButtons.length === 0) {
                showToast('No items available to move to cart', 'info');
                return;
            }
            
            console.log('Moving all to cart, items:', availableButtons.length);
            
            let completed = 0;
            let errors = 0;
            
            availableButtons.forEach(button => {
                const productId = button.getAttribute('data-product-id');
                const productName = button.getAttribute('data-product-name');
                
                // Disable button
                button.disabled = true;
                button.classList.add('loading');
                
                fetch(`/wishlist/move-to-cart/${productId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token
                    }
                })
                .then(response => response.json())
                .then(data => {
                    completed++;
                    if (!data.success) errors++;
                    
                    if (completed === availableButtons.length) {
                        if (errors === 0) {
                            showToast('All items moved to cart successfully!', 'success');
                        } else {
                            showToast(`${completed - errors} items moved to cart, ${errors} failed`, 'info');
                        }
                        setTimeout(() => location.reload(), 1500);
                    }
                })
                .catch(error => {
                    completed++;
                    errors++;
                    console.error('Move all error:', error);
                    
                    if (completed === availableButtons.length) {
                        showToast(`${completed - errors} items moved to cart, ${errors} failed`, 'info');
                        setTimeout(() => location.reload(), 1500);
                    }
                });
            });
        }

        // Update wishlist count
        function updateWishlistCount() {
            const wishlistItems = document.querySelectorAll('.wishlist-item').length;
            const countElement = document.getElementById('wishlistItemCount');
            if (countElement) {
                countElement.textContent = wishlistItems;
            }
            
            // Update header badge
            const headerBadge = document.getElementById('wishlistCount');
            if (headerBadge) {
                headerBadge.textContent = wishlistItems;
                headerBadge.style.display = wishlistItems > 0 ? 'inline' : 'none';
            }
        }

        // Update cart count
        function updateCartCount(count) {
            const cartBadge = document.getElementById('cartCount');
            if (cartBadge) {
                cartBadge.textContent = count;
                cartBadge.style.display = count > 0 ? 'inline' : 'none';
            }
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toastNotification');
            const icon = document.getElementById('toastIcon');
            const messageEl = document.getElementById('toastMessage');
            
            if (!toast || !icon || !messageEl) {
                console.error('Toast elements not found');
                return;
            }
            
            // Set message
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
            
            // Show toast
            toast.classList.remove('hidden');
            
            // Auto hide after 3 seconds
            setTimeout(() => hideToast(), 3000);
        }

        // Hide toast notification
        window.hideToast = function() {
            const toast = document.getElementById('toastNotification');
            if (toast) {
                toast.classList.add('hidden');
            }
        }
    });
    </script>
@endsection