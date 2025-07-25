@extends('layouts.app')

@section('title', 'SneakerFlash - Premium Sneakers Store')

@section('content')
    <!-- Hero Section -->
    <section class="relative h-screen flex items-center justify-center bg-gradient-to-r from-blue-600 to-purple-700">
        <div class="absolute inset-0 bg-black opacity-40"></div>
        
        <div class="relative z-10 text-center text-white max-w-4xl mx-auto px-4">
            <h1 class="text-5xl md:text-7xl font-bold mb-6">
                Step Into 
                <span class="text-yellow-400">Style</span>
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-gray-200">
                Discover premium sneakers from the world's top brands
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/products" class="btn btn-primary text-lg px-8 py-4 rounded-lg">
                    Shop Now
                </a>
                <a href="/products?featured=1" class="btn btn-outline text-lg px-8 py-4 rounded-lg text-white border-white hover:bg-white hover:text-gray-900">
                    Featured Products
                </a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
                <p class="text-gray-600 text-lg">Find your perfect style</p>
            </div>
            
            @if(isset($categories) && $categories->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($categories as $category)
                        <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300">
                            <div class="aspect-w-16 aspect-h-12">
                                @if($category->image)
                                    <img src="{{ Storage::url($category->image) }}" 
                                         alt="{{ $category->name }}"
                                         class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    @php
                                        $colors = [
                                            'from-red-400 to-pink-600',
                                            'from-green-400 to-blue-600',
                                            'from-purple-400 to-indigo-600',
                                            'from-yellow-400 to-orange-600',
                                            'from-blue-400 to-purple-600',
                                            'from-pink-400 to-red-600'
                                        ];
                                        $colorClass = $colors[$loop->index % count($colors)];
                                    @endphp
                                    <div class="w-full h-64 bg-gradient-to-br {{ $colorClass }} flex items-center justify-center">
                                        <i class="fas fa-shoe-prints text-6xl text-white opacity-80"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="absolute inset-0 bg-black bg-opacity-40 group-hover:bg-opacity-60 transition-all duration-300"></div>
                            
                            <div class="absolute inset-0 flex flex-col justify-end p-6 text-white">
                                <h3 class="text-2xl font-bold mb-2">{{ $category->name }}</h3>
                                <p class="text-gray-200 mb-4">{{ $category->description ?? 'Quality sneakers for every occasion' }}</p>
                                <a href="/categories/{{ $category->slug }}" 
                                   class="inline-flex items-center text-yellow-400 hover:text-yellow-300 font-medium">
                                    Shop Now 
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Default Categories when no database data -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300">
                        <div class="w-full h-64 bg-gradient-to-br from-red-400 to-pink-600 flex items-center justify-center">
                            <i class="fas fa-basketball-ball text-6xl text-white opacity-80"></i>
                        </div>
                        <div class="absolute inset-0 bg-black bg-opacity-40 group-hover:bg-opacity-60 transition-all duration-300"></div>
                        <div class="absolute inset-0 flex flex-col justify-end p-6 text-white">
                            <h3 class="text-2xl font-bold mb-2">Basketball Shoes</h3>
                            <p class="text-gray-200 mb-4">High-performance basketball sneakers</p>
                            <a href="/categories/basketball-shoes" class="inline-flex items-center text-yellow-400 hover:text-yellow-300 font-medium">
                                Shop Now <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300">
                        <div class="w-full h-64 bg-gradient-to-br from-green-400 to-blue-600 flex items-center justify-center">
                            <i class="fas fa-walking text-6xl text-white opacity-80"></i>
                        </div>
                        <div class="absolute inset-0 bg-black bg-opacity-40 group-hover:bg-opacity-60 transition-all duration-300"></div>
                        <div class="absolute inset-0 flex flex-col justify-end p-6 text-white">
                            <h3 class="text-2xl font-bold mb-2">Casual Sneakers</h3>
                            <p class="text-gray-200 mb-4">Comfortable everyday wear</p>
                            <a href="/categories/casual-sneakers" class="inline-flex items-center text-yellow-400 hover:text-yellow-300 font-medium">
                                Shop Now <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300">
                        <div class="w-full h-64 bg-gradient-to-br from-purple-400 to-indigo-600 flex items-center justify-center">
                            <i class="fas fa-running text-6xl text-white opacity-80"></i>
                        </div>
                        <div class="absolute inset-0 bg-black bg-opacity-40 group-hover:bg-opacity-60 transition-all duration-300"></div>
                        <div class="absolute inset-0 flex flex-col justify-end p-6 text-white">
                            <h3 class="text-2xl font-bold mb-2">Running Shoes</h3>
                            <p class="text-gray-200 mb-4">Engineered for performance</p>
                            <a href="/categories/running-shoes" class="inline-flex items-center text-yellow-400 hover:text-yellow-300 font-medium">
                                Shop Now <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Featured Products</h2>
                <p class="text-gray-600 text-lg">Hand-picked favorites from our collection</p>
            </div>
            
            @if(isset($featuredProducts) && $featuredProducts->count() > 0)
                <!-- Real Products from Database -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    @foreach($featuredProducts as $product)
                        <div class="product-card bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="relative">
                                <a href="/products/{{ $product->slug }}">
                                    @if($product->images && count($product->images) > 0)
                                        <img src="{{ Storage::url($product->images[0]) }}" 
                                             alt="{{ $product->name }}"
                                             class="w-full h-64 object-cover">
                                    @else
                                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-shoe-prints text-4xl text-gray-400"></i>
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
                                
                                <div class="absolute top-3 right-3">
                                    <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                        Featured
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <div class="mb-2">
                                    <span class="text-sm text-gray-500">{{ $product->category->name ?? 'Category' }}</span>
                                </div>
                                
                                <h3 class="font-semibold text-gray-900 mb-2">
                                    <a href="/products/{{ $product->slug }}" class="hover:text-blue-600 transition-colors">
                                        {{ $product->name }}
                                    </a>
                                </h3>
                                
                                @if($product->brand)
                                    <p class="text-sm text-gray-600 mb-3">{{ $product->brand }}</p>
                                @endif
                                
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
            @else
                <!-- Demo Products when no database data -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    @for($i = 1; $i <= 4; $i++)
                        <div class="product-card bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="relative">
                                <div class="w-full h-64 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                    <i class="fas fa-shoe-prints text-4xl text-gray-400"></i>
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
                                
                                <h3 class="font-semibold text-gray-900 mb-2">
                                    <a href="/products" class="hover:text-blue-600 transition-colors">
                                        Sample Product {{ $i }}
                                    </a>
                                </h3>
                                
                                <p class="text-sm text-gray-600 mb-3">Sample Brand</p>
                                
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-lg font-bold text-gray-900">
                                        Rp {{ number_format(1500000 + ($i * 250000), 0, ',', '.') }}
                                    </span>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <button onclick="addToCart({{ $i }})" 
                                            class="flex-1 btn btn-primary text-sm">
                                        <i class="fas fa-cart-plus mr-1"></i>
                                        Add to Cart
                                    </button>
                                    <a href="/products" 
                                       class="btn btn-outline text-sm px-3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            @endif
            
            <div class="text-center mt-12">
                <a href="/products" class="btn btn-primary text-lg px-8 py-3">
                    View All Products
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Latest Products -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Latest Arrivals</h2>
                <p class="text-gray-600 text-lg">Fresh drops from top brands</p>
            </div>
            
            @if(isset($latestProducts) && $latestProducts->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    @foreach($latestProducts->take(6) as $product)
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="relative">
                                <a href="/products/{{ $product->slug }}">
                                    @if($product->images && count($product->images) > 0)
                                        <img src="{{ Storage::url($product->images[0]) }}" 
                                             alt="{{ $product->name }}"
                                             class="w-full h-48 object-cover">
                                    @else
                                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-3xl text-gray-400"></i>
                                        </div>
                                    @endif
                                </a>
                                
                                <div class="absolute top-2 right-2">
                                    <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                        New
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 text-sm">
                                    <a href="/products/{{ $product->slug }}" class="hover:text-blue-600 transition-colors">
                                        {{ $product->name }}
                                    </a>
                                </h3>
                                
                                <div class="mb-3">
                                    @if($product->sale_price)
                                        <span class="text-sm font-bold text-red-600">
                                            Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-sm font-bold text-gray-900">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                                
                                <button onclick="addToCart({{ $product->id }})" 
                                        class="w-full btn btn-primary text-xs py-2">
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Demo Latest Products -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    @for($i = 1; $i <= 6; $i++)
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="relative">
                                <div class="w-full h-48 bg-gradient-to-br from-blue-200 to-purple-300 flex items-center justify-center">
                                    <i class="fas fa-shoe-prints text-3xl text-gray-500"></i>
                                </div>
                                <div class="absolute top-2 right-2">
                                    <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                        New
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 text-sm">
                                    <a href="/products" class="hover:text-blue-600 transition-colors">
                                        Latest Product {{ $i }}
                                    </a>
                                </h3>
                                
                                <div class="mb-3">
                                    <span class="text-sm font-bold text-gray-900">
                                        Rp {{ number_format(900000 + ($i * 150000), 0, ',', '.') }}
                                    </span>
                                </div>
                                
                                <button onclick="addToCart({{ $i }})" 
                                        class="w-full btn btn-primary text-xs py-2">
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    @endfor
                </div>
            @endif
            
            <div class="text-center mt-12">
                <a href="/products" class="btn btn-primary text-lg px-8 py-3">
                    View All Products
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-gray-900 text-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shipping-fast text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Fast Delivery</h3>
                    <p class="text-gray-300">Free shipping on orders over Rp 500,000. Same day delivery in Jakarta.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">100% Authentic</h3>
                    <p class="text-gray-300">All our products are guaranteed authentic from official distributors.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-undo text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Easy Returns</h3>
                    <p class="text-gray-300">Not satisfied? Return within 30 days for a full refund.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="py-16 bg-blue-600">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Stay in the Loop</h2>
            <p class="text-blue-100 mb-8 text-lg">Get notified about new arrivals and exclusive offers</p>
            
            <div class="max-w-md mx-auto flex">
                <input type="email" placeholder="Enter your email" 
                       class="flex-1 px-4 py-3 rounded-l-lg border-0 focus:ring-2 focus:ring-blue-300">
                <button class="btn btn-secondary px-6 py-3 rounded-r-lg rounded-l-none bg-yellow-500 border-yellow-500 hover:bg-yellow-600 hover:border-yellow-600">
                    Subscribe
                </button>
            </div>
            
            <p class="text-blue-100 text-sm mt-4">
                <i class="fas fa-lock mr-1"></i>
                We respect your privacy. Unsubscribe at any time.
            </p>
        </div>
    </section>
@endsection