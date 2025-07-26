@extends('layouts.app')

@section('title', 'SneakerFlash - Premium Sneakers for Everyone')

@section('content')
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-blue-900 to-indigo-800 text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                Step Into <span class="text-yellow-400">Style</span>
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-gray-200">
                Discover the perfect sneakers for every adventure
            </p>
            <div class="space-x-4">
                <a href="/products" class="inline-block bg-yellow-400 text-black px-8 py-3 rounded-lg font-semibold hover:bg-yellow-300 transition-colors">
                    Shop Now
                </a>
                <a href="/products?featured=1" class="inline-block border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-black transition-colors">
                    View Featured
                </a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
                <p class="text-gray-600 text-lg">Find your perfect fit</p>
            </div>
            
            @if(isset($categories) && $categories->count() > 0)
                <!-- Real Categories from Database -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    @foreach($categories as $category)
                        <div class="group">
                            <a href="/categories/{{ $category->slug }}" class="block">
                                <div class="bg-gray-100 rounded-xl p-6 text-center hover:bg-gray-200 transition-colors group-hover:scale-105 transform transition-transform">
                                    <!-- Category Icon - Anda bisa tambahkan field icon di database atau gunakan icon default -->
                                    <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                                        @switch($category->slug)
                                            @case('running-shoes')
                                                <i class="fas fa-running text-2xl text-blue-600"></i>
                                                @break
                                            @case('basketball-shoes')
                                                <i class="fas fa-basketball-ball text-2xl text-blue-600"></i>
                                                @break
                                            @case('casual-shoes')
                                                <i class="fas fa-walking text-2xl text-blue-600"></i>
                                                @break
                                            @case('training-shoes')
                                                <i class="fas fa-dumbbell text-2xl text-blue-600"></i>
                                                @break
                                            @default
                                                <i class="fas fa-shoe-prints text-2xl text-blue-600"></i>
                                        @endswitch
                                    </div>
                                    <h3 class="font-semibold text-gray-900 mb-2">{{ $category->name }}</h3>
                                    @if($category->description)
                                        <p class="text-sm text-gray-600">{{ Str::limit($category->description, 50) }}</p>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback Categories (jika tidak ada data di database) -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    <div class="group">
                        <a href="/categories/running-shoes" class="block">
                            <div class="bg-gray-100 rounded-xl p-6 text-center hover:bg-gray-200 transition-colors group-hover:scale-105 transform transition-transform">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-running text-2xl text-blue-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Running</h3>
                                <p class="text-sm text-gray-600">Performance running shoes</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="group">
                        <a href="/categories/basketball-shoes" class="block">
                            <div class="bg-gray-100 rounded-xl p-6 text-center hover:bg-gray-200 transition-colors group-hover:scale-105 transform transition-transform">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-basketball-ball text-2xl text-blue-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Basketball</h3>
                                <p class="text-sm text-gray-600">High-performance court shoes</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="group">
                        <a href="/categories/casual-shoes" class="block">
                            <div class="bg-gray-100 rounded-xl p-6 text-center hover:bg-gray-200 transition-colors group-hover:scale-105 transform transition-transform">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-walking text-2xl text-blue-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Casual</h3>
                                <p class="text-sm text-gray-600">Everyday comfort</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="group">
                        <a href="/categories/training-shoes" class="block">
                            <div class="bg-gray-100 rounded-xl p-6 text-center hover:bg-gray-200 transition-colors group-hover:scale-105 transform transition-transform">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-dumbbell text-2xl text-blue-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Training</h3>
                                <p class="text-sm text-gray-600">Gym and fitness</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="group">
                        <a href="/categories/skateboard-shoes" class="block">
                            <div class="bg-gray-100 rounded-xl p-6 text-center hover:bg-gray-200 transition-colors group-hover:scale-105 transform transition-transform">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-skating text-2xl text-blue-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Skateboard</h3>
                                <p class="text-sm text-gray-600">Street style</p>
                            </div>
                        </a>
                    </div>
                    
                    <div class="group">
                        <a href="/categories/formal-shoes" class="block">
                            <div class="bg-gray-100 rounded-xl p-6 text-center hover:bg-gray-200 transition-colors group-hover:scale-105 transform transition-transform">
                                <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user-tie text-2xl text-blue-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">Formal</h3>
                                <p class="text-sm text-gray-600">Business & events</p>
                            </div>
                        </a>
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
                        <div class="product-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow">
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
                                
                                <div class="absolute top-3 right-3">
                                    <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                        Featured
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <div class="mb-2">
                                    @if($product->category)
                                        <span class="text-sm text-gray-500">{{ $product->category->name }}</span>
                                    @endif
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-2">{{ $product->name }}</h3>
                                @if($product->brand)
                                    <p class="text-sm text-gray-600 mb-2">{{ $product->brand }}</p>
                                @endif
                                <div class="flex items-center justify-between">
                                    <div>
                                        @if($product->sale_price)
                                            <span class="text-lg font-bold text-red-600">
                                                Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                                            </span>
                                            <span class="text-sm text-gray-500 line-through ml-2">
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-lg font-bold text-gray-900">
                                                Rp {{ number_format($product->price, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Stock Status -->
                                @if($product->stock_quantity > 0)
                                    <p class="text-xs text-green-600 mt-2">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        In stock
                                    </p>
                                @else
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
                <!-- Fallback Products (jika tidak ada data di database) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    @for($i = 1; $i <= 4; $i++)
                        <div class="product-card bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="relative">
                                <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-4xl text-gray-400"></i>
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
                                <h3 class="font-semibold text-gray-900 mb-2">Sample Product {{ $i }}</h3>
                                <p class="text-sm text-gray-600 mb-2">Sample Brand</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-bold text-gray-900">
                                        Rp {{ number_format(rand(500000, 2000000), 0, ',', '.') }}
                                    </span>
                                </div>
                                <p class="text-xs text-green-600 mt-2">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    In stock
                                </p>
                            </div>
                        </div>
                    @endfor
                </div>
            @endif

            <div class="text-center mt-12">
                <a href="/products?featured=1" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    View All Featured Products
                </a>
            </div>
        </div>
    </section>

    <!-- Latest Products -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Latest Arrivals</h2>
                <p class="text-gray-600 text-lg">Fresh kicks just dropped</p>
            </div>
            
            @if(isset($latestProducts) && $latestProducts->count() > 0)
                <!-- Real Latest Products from Database -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6">
                    @foreach($latestProducts as $product)
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <div class="relative">
                                <a href="/products/{{ $product->slug }}">
                                    @if($product->images && count($product->images) > 0)
                                        <img src="{{ Storage::url($product->images[0]) }}" 
                                             alt="{{ $product->name }}"
                                             class="w-full h-40 object-cover">
                                    @else
                                        <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-2xl text-gray-400"></i>
                                        </div>
                                    @endif
                                </a>
                                
                                @if($product->sale_price)
                                    <div class="absolute top-2 left-2">
                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                            Sale
                                        </span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 text-sm">{{ Str::limit($product->name, 20) }}</h3>
                                <div class="flex items-center justify-between">
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
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback Latest Products -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6">
                    @for($i = 1; $i <= 6; $i++)
                        <div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="relative">
                                <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-2xl text-gray-400"></i>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h3 class="font-medium text-gray-900 mb-1 text-sm">New Product {{ $i }}</h3>
                                <span class="text-sm font-bold text-gray-900">
                                    Rp {{ number_format(rand(400000, 1500000), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endfor
                </div>
            @endif

            <div class="text-center mt-12">
                <a href="/products?sort=latest" class="inline-block bg-gray-800 text-white px-8 py-3 rounded-lg hover:bg-gray-900 transition-colors font-medium">
                    View All New Arrivals
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-blue-600 to-purple-700 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Join the SneakerFlash Community</h2>
            <p class="text-xl mb-8 text-gray-200">Get exclusive access to new releases and special offers</p>
            <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-4">
                <input type="email" placeholder="Enter your email" 
                       class="px-6 py-3 rounded-lg text-gray-900 w-full sm:w-80">
                <button class="bg-yellow-400 text-black px-8 py-3 rounded-lg font-semibold hover:bg-yellow-300 transition-colors">
                    Subscribe
                </button>
            </div>
        </div>
    </section>
@endsection