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
                        <button type="button" id="clearWishlistBtn" class="border border-red-300 text-red-600 px-6 py-2 rounded-lg hover:bg-red-50 transition-colors font-medium">
                            <i class="fas fa-trash mr-2"></i>
                            Clear Wishlist
                        </button>
                    </div>
                </div>
            </div>

            <!-- Wishlist Items Grid - Same as Products Grid -->
            <div id="wishlistGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($wishlists as $wishlist)
                    @if($wishlist->product)
                        @php
$product        = $wishlist->product;
$productPrice   = $product->price ?? 0;
$salePrice      = $product->sale_price ?? null;
$finalPrice     = $salePrice && $salePrice < $productPrice ? $salePrice : $productPrice;

// 🔧 BERSIHKAN NAMA: hilangkan sku_parent & pola size di ujung
$originalName = $product->name ?? 'Unknown Product';
$skuParent    = $product->sku_parent ?? '';

$cleanProductName = $originalName;

if (!empty($skuParent)) {
    // hapus "- <sku_parent>" di akhir atau tengah
    $cleanProductName = preg_replace('/\s*-\s*' . preg_quote($skuParent, '/') . '\s*$/', '', $cleanProductName);
    $cleanProductName = preg_replace('/\s*'  . preg_quote($skuParent, '/') . '\s*$/', '', $cleanProductName);
    $cleanProductName = preg_replace('/\s*-\s*' . preg_quote($skuParent, '/') . '\s*/', '', $cleanProductName);
}

// hapus pola ukuran di ujung nama
$cleanProductName = preg_replace('/\s*-\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanProductName);
$cleanProductName = preg_replace('/\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanProductName);
// hapus token huruf/angka terakhir seperti "- MRCXLB4" atau "- 47"
$cleanProductName = preg_replace('/\s*-\s*[A-Z0-9.]+\s*$/i', '', $cleanProductName);

$cleanProductName = trim($cleanProductName, " -");


    // === AMBIL VARIAN DARI accessor Product::getSizeVariantsAttribute() ===
    // accessor itu mengembalikan koleksi "produk saudara" (id,name,sku,available_sizes,stock_quantity)
    $rawVariants = $product->size_variants ?? collect();

    // Pastikan array
    if ($rawVariants instanceof \Illuminate\Support\Collection) {
        $rawVariants = $rawVariants->toArray();
    } elseif (!is_array($rawVariants)) {
        $rawVariants = [];
    }

    // Helper kecil: coba ambil size dari nama jika available_sizes tidak jelas
    $extractSize = function (?string $name) {
        if (!$name) return null;
        if (preg_match('/Size\s+([0-9]{1,2}(?:\.[0-9])?)/i', $name, $m)) return $m[1];
        if (preg_match('/(?:-|)\s*([0-9]{1,2}(?:\.[0-9])?)\s*$/', $name, $m)) return $m[1];
        return null;
    };

    // Normalisasi ke format yang dimengerti modal: size + stock (+ price)
    $sizeVariants = [];
    foreach ($rawVariants as $v) {
        $a = is_array($v) ? $v : (array)$v;

        // available_sizes bisa array/string/kosong
        $sizes = [];
        if (isset($a['available_sizes'])) {
            if (is_string($a['available_sizes'])) {
                $decoded = json_decode($a['available_sizes'], true);
                $sizes = is_array($decoded) ? $decoded : (strlen($a['available_sizes']) ? [$a['available_sizes']] : []);
            } elseif (is_array($a['available_sizes'])) {
                $sizes = $a['available_sizes'];
            }
        }

        $size  = (count($sizes) === 1) ? $sizes[0] : $extractSize($a['name'] ?? null);
        $stock = (int)($a['stock_quantity'] ?? 0);

        $sizeVariants[] = [
            'id'             => $a['id'] ?? null,
            'sku'            => $a['sku'] ?? null,
            'size'           => $size ?: 'Unknown',
            'stock'          => $stock,
            // kita tidak punya harga per-varian dari accessor → pakai fallback harga produk
            'price'          => $finalPrice,
            'original_price' => $productPrice,
        ];
    }

    // fallback terakhir jika tetap kosong → pakai available_sizes produk ini
    if (empty($sizeVariants) && is_array($product->available_sizes) && count($product->available_sizes)) {
        foreach ($product->available_sizes as $s) {
            $sizeVariants[] = [
                'id'             => $product->id,
                'sku'            => $product->sku ?? null,
                'size'           => $s,
                'stock'          => (int)($product->stock_quantity ?? 0),
                'price'          => $finalPrice,
                'original_price' => $productPrice,
            ];
        }
    }

    $hasVariants = count($sizeVariants) > 0;
@endphp


                        
                        <div class="product-card wishlist-item group bg-white rounded-2xl overflow-hidden border border-gray-100 hover:border-gray-200 hover:shadow-lg transition-all duration-300 relative">
                            <!-- Product Image -->
<div class="relative aspect-square overflow-hidden">
    <a href="{{ route('products.show', $product->slug ?? $product->id) }}">
        @php
            // Ambil kandidat path: prioritaskan $product->images kalau ada
            $imageUrl = null;

            // 1) Jika ada images array
            $images = $product->images ?? null;
            if (is_string($images)) {
                // kemungkinan disimpan sebagai JSON string
                $decoded = json_decode($images, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $images = $decoded;
                }
            }
            if (is_array($images) && count($images) > 0) {
                $imagePath = $images[0];
            } else {
                // fallback ke single field
                $imagePath = $product->image ?? null;
            }

            if ($imagePath) {
                // Case A: sudah full URL
                if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                    $imageUrl = $imagePath;
                }
                // Case B: mulai dengan /storage/
                elseif (str_starts_with($imagePath, '/storage/')) {
                    $imageUrl = config('app.url') . $imagePath;
                }
                // Case C: relative storage path (products/filename.jpg)
                elseif (str_starts_with($imagePath, 'products/')) {
                    $imageUrl = config('app.url') . '/storage/' . ltrim($imagePath, '/');
                }
                // Case D: asset path (assets/ atau images/)
                elseif (str_starts_with($imagePath, 'assets/') || str_starts_with($imagePath, 'images/')) {
                    $imageUrl = asset($imagePath);
                }
                // Case E: cuma filename → asumsi di storage/products
                elseif (!str_contains($imagePath, '/')) {
                    $imageUrl = config('app.url') . '/storage/products/' . $imagePath;
                }
                // Case F: fallback generic ke /storage/<path>
                else {
                    $imageUrl = config('app.url') . '/storage/' . ltrim($imagePath, '/');
                }
            }
        @endphp

        @if($imageUrl)
            <img src="{{ $imageUrl }}"
                 alt="{{ $cleanProductName }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                 onerror="this.src='{{ asset('images/default-product.png') }}'">
        @else
            <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                <i class="fas fa-image text-4xl text-gray-300"></i>
            </div>
        @endif
    </a>
                                
                                <!-- Badges -->
                                <div class="absolute top-3 left-3 flex flex-col gap-2 z-10">
                                    @if($salePrice && $salePrice < $productPrice)
                                        @php
                                            $discount = round((($productPrice - $salePrice) / $productPrice) * 100);
                                        @endphp
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-medium">
                                            -{{ $discount }}%
                                        </span>
                                    @endif
                                    @php $totalStock = $product->total_stock ?? ($product->stock_quantity ?? 0); @endphp
@if($totalStock <= 0)
    <span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full font-medium">Out of Stock</span>
@elseif($totalStock < 10)
    <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full font-medium">Low Stock</span>
@endif
                                </div>

                                <!-- Remove from wishlist button -->
                                <button type="button" class="remove-wishlist-btn absolute top-3 right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-md hover:shadow-lg transition-all duration-200" 
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $cleanProductName }}"
                                        title="Remove from wishlist">
                                    <i class="fas fa-times text-gray-600 hover:text-red-500 transition-colors"></i>
                                </button>

                                <!-- Added to wishlist date -->
                                <div class="absolute bottom-3 left-3">
                                    <span class="bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded-full">
                                        Added {{ $wishlist->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <!-- Hidden size data for modal (same as products/index.blade.php) -->
                                @if($hasVariants)
    <div id="sizeContainer-{{ $product->id }}" class="hidden">
        @foreach($sizeVariants as $v)
            @php $isAvailable = ($v['stock'] ?? 0) > 0; @endphp
            <div class="size-badge"
                 data-size="{{ $v['size'] }}"
                 data-stock="{{ $v['stock'] }}"
                 data-product-id="{{ $v['id'] ?? $product->id }}"
                 data-available="{{ $isAvailable ? 'true' : 'false' }}"
                 data-price="{{ $v['price'] }}"
                 data-original-price="{{ $v['original_price'] }}">
            </div>
        @endforeach
    </div>
@endif


                            </div>
                            
                            <!-- Product Details -->
                            <div class="p-4">
                                <div class="mb-2">
                                    <span class="text-xs text-gray-500 uppercase tracking-wide">
                                        {{ $product->category->name ?? 'Products' }}
                                        @if($product->brand)
                                            • {{ $product->brand }}
                                        @endif
                                    </span>
                                </div>
                                
                                <h3 class="font-semibold text-gray-900 mb-3 text-sm leading-tight line-clamp-2">
                                    <a href="{{ route('products.show', $product->slug ?? $product->id) }}" 
                                       class="hover:text-blue-600 transition-colors">
                                        {{ $cleanProductName }}
                                    </a>
                                </h3>
                                
                                <!-- Display available sizes and colors if exist -->
@if($hasVariants || $product->available_colors)
    <div class="mb-3 text-xs text-gray-500">
        @if($hasVariants)
            <div class="mb-1">
                <span class="font-medium">Sizes:</span>
                @php
  $agg = is_array($product->aggregated_sizes ?? null)
        ? $product->aggregated_sizes
        : [];
  $sizesToShow = array_slice($agg, 0, 3);
@endphp
{{ implode(', ', $sizesToShow) }}
@if(count($agg) > 3)
  <span class="text-gray-400">+{{ count($agg) - 3 }} more</span>
@endif

            </div>
        @endif
                                        @if($product->available_colors)
            <div>
                <span class="font-medium">Colors:</span>
                {{ is_array($product->available_colors) ? implode(', ', array_slice($product->available_colors, 0, 3)) : $product->available_colors }}
                @if(is_array($product->available_colors) && count($product->available_colors) > 3)
                    <span class="text-gray-400">+{{ count($product->available_colors) - 3 }} more</span>
                @endif
            </div>
        @endif
    </div>
@endif
                                
                                <!-- Price -->
                                <div class="mb-4">
                                    @php
  $minPrice = $product->min_price ?? ($salePrice && $salePrice < $productPrice ? $salePrice : $productPrice);
@endphp

@if($minPrice < $productPrice)
  <div class="flex items-center space-x-2">
    <span class="text-lg font-bold text-red-600">
      Rp {{ number_format($minPrice, 0, ',', '.') }}
    </span>
    <span class="text-sm text-gray-400 line-through">
      Rp {{ number_format($productPrice, 0, ',', '.') }}
    </span>
  </div>
@else
  <span class="text-lg font-bold text-gray-900">
    Rp {{ number_format($minPrice, 0, ',', '.') }}
  </span>
@endif

                                </div>
                                
                                <!-- Action Buttons - Same as Products Index -->
                                <div class="flex items-center space-x-2">
@php $totalStock = $product->total_stock ?? ($product->stock_quantity ?? 0); @endphp
@if($totalStock > 0)
  @if($hasVariants)
    <button type="button"
            class="size-select-btn flex-1 bg-gray-900 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors"
            data-product-id="{{ $product->id }}"
            data-sku-parent="{{ $product->sku_parent ?? '' }}"
            data-product-name="{{ $cleanProductName }}"
            data-price="{{ $product->min_price ?? $finalPrice }}"
            data-original-price="{{ $productPrice }}">
      <i class="fas fa-shopping-cart mr-1"></i>
      Select Size
    </button>
  @else
    <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form flex-1">
      @csrf
      <input type="hidden" name="product_id" value="{{ $product->id }}">
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

                                    <a href="{{ route('products.show', $product->slug ?? $product->id) }}" class="px-3 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
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

    <!-- Size Selection Modal (Same as products/index.blade.php) -->
    <div id="sizeSelectionModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white rounded-t-2xl border-b border-gray-200 p-6 z-10">
                <div class="flex items-center justify-between">
                    <h3 id="modalProductName" class="text-xl font-bold text-gray-900">Select Size</h3>
                    <button id="closeModalBtn" type="button" class="text-gray-400 hover:text-gray-600 transition-colors p-2">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
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
                            <span id="selectedSizePrice" class="text-sm font-semibold text-blue-700">-</span>
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

    /* Animation for removing items */
    .wishlist-item.removing {
        opacity: 0.5;
        transform: scale(0.95);
        transition: all 0.3s ease;
    }

    /* Size option styles */
    .size-option {
        transition: all 0.2s ease;
    }

    .size-option:hover:not(.disabled) {
        border-color: #3B82F6;
        background-color: #EFF6FF;
    }

    .size-option.selected {
        border-color: #3B82F6 !important;
        background-color: #EFF6FF !important;
        color: #1D4ED8;
    }

    .size-option.disabled {
        background-color: #F3F4F6;
        color: #9CA3AF;
        cursor: not-allowed;
    }

    /* Line clamp utility */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    </style>

    <!-- Enhanced JavaScript with Size Selection (Same as products/index.blade.php) -->
    <script>
    console.log('🚀 Enhanced Wishlist JavaScript with Size Selection...');

    window.addEventListener('load', function() {
        setupSizeSelection();
        setupWishlistActions();
    });

    function setupSizeSelection() {
        document.addEventListener('click', handleClick);
    }

    function handleClick(e) {
        // Size select button
        if (e.target.closest('.size-select-btn')) {
            e.preventDefault();
            openSizeModal(e.target.closest('.size-select-btn'));
            return;
        }
        
        // Close modal
        if (e.target.id === 'closeModalBtn' || e.target.closest('#closeModalBtn') || e.target.id === 'sizeSelectionModal') {
            closeModal();
            return;
        }
        
        // Size option
        if (e.target.closest('.size-option')) {
            var option = e.target.closest('.size-option');
            if (!option.classList.contains('disabled')) {
                selectSize(option);
            }
            return;
        }
    }

    function openSizeModal(button) {
        var productId = button.getAttribute('data-product-id');
        var productName = button.getAttribute('data-product-name');
        var defaultPrice = button.getAttribute('data-price') || '0';
        
        console.log('Opening modal for:', productName, 'Price:', defaultPrice);
        
        var modal = document.getElementById('sizeSelectionModal');
        var title = document.getElementById('modalProductName');
        var container = document.getElementById('sizeOptionsContainer');
        
        if (!modal || !container) return;
        
        // Set title
        if (title) title.textContent = 'Select Size - ' + productName;
        
        // Get sizes from product card
        var productCard = button.closest('.product-card');
        var sizeContainer = productCard ? productCard.querySelector('#sizeContainer-' + productId) : null;
        
        // Clear container
        container.innerHTML = '';
        
        if (sizeContainer) {
            var badges = sizeContainer.querySelectorAll('.size-badge');
            
            badges.forEach(function(badge) {
                var size = badge.getAttribute('data-size');
                var stock = badge.getAttribute('data-stock');
                var productVariantId = badge.getAttribute('data-product-id');
                var available = badge.getAttribute('data-available') === 'true';
                
                var price = badge.getAttribute('data-price') || defaultPrice;
                var originalPrice = badge.getAttribute('data-original-price') || defaultPrice;
                
                var div = document.createElement('div');
                div.className = 'size-option cursor-pointer p-4 border-2 rounded-lg text-center transition-all ' + 
                    (available ? 'border-gray-300 hover:border-blue-500' : 'disabled border-gray-200 bg-gray-50');
                
                div.setAttribute('data-product-id', productVariantId);
                div.setAttribute('data-size', size);
                div.setAttribute('data-stock', stock);
                div.setAttribute('data-price', price);
                div.setAttribute('data-original-price', originalPrice);
                
                div.innerHTML = `
                    <div class="font-semibold text-lg ${available ? 'text-gray-900' : 'text-gray-400'}">${size}</div>
                    <div class="text-xs mt-1 ${available ? 'text-gray-600' : 'text-gray-400'}">
                        ${available ? stock + ' available' : 'Out of stock'}
                    </div>
                `;
                
                container.appendChild(div);
            });
        }
        
        // Show modal
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function selectSize(element) {
        var productId = element.getAttribute('data-product-id');
        var size = element.getAttribute('data-size');
        var stock = element.getAttribute('data-stock');
        var price = element.getAttribute('data-price');
        
        console.log('📏 Size selected:', size, 'Stock:', stock, 'Price:', price);
        
        // Remove previous selections
        document.querySelectorAll('.size-option').forEach(function(opt) {
            opt.classList.remove('selected');
        });
        
        // Select current option
        element.classList.add('selected');
        
        // Update UI elements
        var sizeInfo = document.getElementById('selectedSizeInfo');
        var sizeDisplay = document.getElementById('selectedSizeDisplay');
        var sizeStock = document.getElementById('selectedSizeStock');
        var sizePriceElement = document.getElementById('selectedSizePrice');
        var form = document.getElementById('sizeAddToCartForm');
        var productInput = document.getElementById('selectedProductId');
        var sizeInput = document.getElementById('selectedSizeValue');
        
        if (sizeDisplay) sizeDisplay.textContent = size;
        if (sizeStock) sizeStock.textContent = stock + ' available';
        
        // Update price
        if (sizePriceElement) {
            var formattedPrice = 'Rp ' + new Intl.NumberFormat('id-ID').format(parseInt(price));
            sizePriceElement.textContent = formattedPrice;
            console.log('💰 Price updated to:', formattedPrice);
        }
        
        if (productInput) productInput.value = productId;
        if (sizeInput) sizeInput.value = size;
        
        if (sizeInfo) sizeInfo.classList.remove('hidden');
        if (form) form.classList.remove('hidden');
    }

    function closeModal() {
        var modal = document.getElementById('sizeSelectionModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Reset form
        var form = document.getElementById('sizeAddToCartForm');
        var sizeInfo = document.getElementById('selectedSizeInfo');
        if (form) form.classList.add('hidden');
        if (sizeInfo) sizeInfo.classList.add('hidden');
        
        // Clear selections
        document.querySelectorAll('.size-option').forEach(function(opt) {
            opt.classList.remove('selected');
        });
    }

    function setupWishlistActions() {
        const WISHLIST_CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Remove from wishlist functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-wishlist-btn')) {
                e.preventDefault();
                e.stopPropagation();
                
                const btn = e.target.closest('.remove-wishlist-btn');
                const productId = btn.dataset.productId;
                const productName = btn.dataset.productName || 'Product';
                
                if (!productId) return;
                
                if (!confirm(`Remove ${productName} from your wishlist?`)) return;
                
                btn.disabled = true;
                const wishlistItem = btn.closest('.wishlist-item');
                if (wishlistItem) {
                    wishlistItem.classList.add('removing');
                }

                fetch(`/wishlist/remove/${productId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': WISHLIST_CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (wishlistItem) {
                            wishlistItem.remove();
                        }
                        updateWishlistCount();
                        showToast(`${productName} removed from wishlist`, 'success');
                    } else {
                        showToast(data.message || 'Failed to remove from wishlist', 'error');
                        btn.disabled = false;
                        if (wishlistItem) {
                            wishlistItem.classList.remove('removing');
                        }
                    }
                })
                .catch(error => {
                    console.error('Remove wishlist error:', error);
                    showToast('Failed to remove from wishlist', 'error');
                    btn.disabled = false;
                    if (wishlistItem) {
                        wishlistItem.classList.remove('removing');
                    }
                });
            }
        });

        // Clear wishlist functionality
        const clearBtn = document.getElementById('clearWishlistBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to clear your entire wishlist?')) return;
                
                this.disabled = true;
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Clearing...';

                fetch('/wishlist/clear', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': WISHLIST_CSRF,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Wishlist cleared successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Failed to clear wishlist', 'error');
                        this.disabled = false;
                        this.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Clear wishlist error:', error);
                    showToast('Failed to clear wishlist', 'error');
                    this.disabled = false;
                    this.innerHTML = originalText;
                });
            });
        }
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
        
        // Show empty state if no items
        if (wishlistItems === 0) {
            setTimeout(() => location.reload(), 2000);
        }
    }

    // Toast notification functions
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        const icon = document.getElementById('toastIcon');
        const messageElement = document.getElementById('toastMessage');
        
        if (!toast || !icon || !messageElement) return;
        
        // Set message
        messageElement.textContent = message;
        
        // Set icon based on type
        icon.className = type === 'error' 
            ? 'fas fa-exclamation-circle text-red-500'
            : type === 'info'
            ? 'fas fa-info-circle text-blue-500'
            : 'fas fa-check-circle text-green-500';
        
        // Show toast
        toast.classList.remove('hidden');
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            hideToast();
        }, 3000);
    }

    function hideToast() {
        const toast = document.getElementById('toastNotification');
        if (toast) {
            toast.classList.add('hidden');
        }
    }

    // Handle add to cart form submissions
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('add-to-cart-form') || e.target.id === 'sizeAddToCartForm') {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Product added to cart!', 'success');
                        
                        // Update cart count if provided
                        if (data.cartCount !== undefined) {
                            const cartBadge = document.getElementById('cartCount');
                            if (cartBadge) {
                                cartBadge.textContent = data.cartCount;
                                cartBadge.style.display = data.cartCount > 0 ? 'inline' : 'none';
                            }
                        }
                        
                        // Close modal if it was from size selection
                        if (form.id === 'sizeAddToCartForm') {
                            closeModal();
                        }
                    } else {
                        showToast(data.message || 'Failed to add product to cart', 'error');
                    }
                })
                .catch(error => {
                    console.error('Add to cart error:', error);
                    showToast('Failed to add product to cart', 'error');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                });
            }
        }
    });
    </script>
    {{-- sementara untuk debug --}}
{{-- <pre>{{ print_r($product->size_variants, true) }}</pre> --}}

@endsection