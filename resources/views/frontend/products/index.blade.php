@extends('layouts.app')

@section('title', 'Products - SneakerFlash')

@section('content')
    <!-- Page Header -->
    <section class="bg-gray-100 py-8">
        <div class="container mx-auto px-4">
            <nav class="text-sm mb-4">
                <ol class="flex space-x-2 text-gray-600">
                    <li><a href="/" class="hover:text-blue-600">Home</a></li>
                    <li>/</li>
                    <li class="text-gray-900">Products</li>
                </ol>
            </nav>
            
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">All Products</h1>
                <div class="text-gray-600">
                    24 products found
                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <aside class="lg:w-1/4">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h3 class="font-semibold text-gray-900 mb-4">Filters</h3>
                    
                    <form method="GET">
                        <!-- Search -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" placeholder="Search products..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Categories -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                <option value="">All Categories</option>
                                <option value="basketball">Basketball Shoes</option>
                                <option value="casual">Casual Sneakers</option>
                                <option value="running">Running Shoes</option>
                            </select>
                        </div>

                        <!-- Brands -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                            <select name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                <option value="">All Brands</option>
                                <option value="nike">Nike</option>
                                <option value="adidas">Adidas</option>
                                <option value="converse">Converse</option>
                                <option value="vans">Vans</option>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="min_price" placeholder="Min" 
                                       class="px-3 py-2 border border-gray-300 rounded-md">
                                <input type="number" name="max_price" placeholder="Max" 
                                       class="px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <button type="submit" class="w-full btn btn-primary">Apply Filters</button>
                            <a href="/products" class="w-full btn btn-outline">Clear Filters</a>
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Products Grid -->
            <main class="lg:w-3/4">
                <!-- Sort Options -->
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Sort by:</span>
                        <select name="sort" class="px-3 py-2 border border-gray-300 rounded-md">
                            <option value="latest">Latest</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="name">Name A-Z</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <button class="p-2 text-blue-600 hover:text-blue-800">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="p-2 text-gray-600 hover:text-blue-600">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Product 1 -->
                    <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <div class="w-full h-64 bg-gradient-to-br from-red-200 to-red-300 flex items-center justify-center">
                                <i class="fas fa-shoe-prints text-4xl text-gray-500"></i>
                            </div>
                            <div class="absolute top-3 right-3">
                                <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                    Featured
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="mb-2">
                                <span class="text-sm text-gray-500">Basketball • Nike</span>
                            </div>
                            
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <a href="/products/air-jordan-1" class="hover:text-blue-600 transition-colors">
                                    Air Jordan 1 Retro High
                                </a>
                            </h3>
                            
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg font-bold text-red-600">
                                        Rp 2,250,000
                                    </span>
                                    <span class="text-sm text-gray-500 line-through">
                                        Rp 2,500,000
                                    </span>
                                </div>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    <span class="text-sm text-gray-600 ml-1">4.8</span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button onclick="addToCart(1)" class="flex-1 btn btn-primary text-sm">
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    Add to Cart
                                </button>
                                <a href="/products/air-jordan-1" class="btn btn-outline text-sm px-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Product 2 -->
                    <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <div class="w-full h-64 bg-gradient-to-br from-blue-200 to-blue-300 flex items-center justify-center">
                                <i class="fas fa-shoe-prints text-4xl text-gray-500"></i>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="mb-2">
                                <span class="text-sm text-gray-500">Casual • Converse</span>
                            </div>
                            
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <a href="/products/chuck-taylor" class="hover:text-blue-600 transition-colors">
                                    Chuck Taylor All Star
                                </a>
                            </h3>
                            
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-lg font-bold text-gray-900">
                                    Rp 950,000
                                </span>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    <span class="text-sm text-gray-600 ml-1">4.5</span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button onclick="addToCart(2)" class="flex-1 btn btn-primary text-sm">
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    Add to Cart
                                </button>
                                <a href="/products/chuck-taylor" class="btn btn-outline text-sm px-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Product 3 -->
                    <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <div class="w-full h-64 bg-gradient-to-br from-green-200 to-green-300 flex items-center justify-center">
                                <i class="fas fa-shoe-prints text-4xl text-gray-500"></i>
                            </div>
                            <div class="absolute top-3 left-3">
                                <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                    -15%
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="mb-2">
                                <span class="text-sm text-gray-500">Running • Nike</span>
                            </div>
                            
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <a href="/products/air-max-270" class="hover:text-blue-600 transition-colors">
                                    Air Max 270
                                </a>
                            </h3>
                            
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg font-bold text-red-600">
                                        Rp 1,700,000
                                    </span>
                                    <span class="text-sm text-gray-500 line-through">
                                        Rp 2,000,000
                                    </span>
                                </div>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    <span class="text-sm text-gray-600 ml-1">4.7</span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button onclick="addToCart(3)" class="flex-1 btn btn-primary text-sm">
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    Add to Cart
                                </button>
                                <a href="/products/air-max-270" class="btn btn-outline text-sm px-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Product 4 -->
                    <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <div class="w-full h-64 bg-gradient-to-br from-purple-200 to-purple-300 flex items-center justify-center">
                                <i class="fas fa-shoe-prints text-4xl text-gray-500"></i>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="mb-2">
                                <span class="text-sm text-gray-500">Lifestyle • Adidas</span>
                            </div>
                            
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <a href="/products/stan-smith" class="hover:text-blue-600 transition-colors">
                                    Stan Smith Classic
                                </a>
                            </h3>
                            
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-lg font-bold text-gray-900">
                                    Rp 1,250,000
                                </span>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    <span class="text-sm text-gray-600 ml-1">4.6</span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button onclick="addToCart(4)" class="flex-1 btn btn-primary text-sm">
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    Add to Cart
                                </button>
                                <a href="/products/stan-smith" class="btn btn-outline text-sm px-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Product 5 -->
                    <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <div class="w-full h-64 bg-gradient-to-br from-yellow-200 to-yellow-300 flex items-center justify-center">
                                <i class="fas fa-shoe-prints text-4xl text-gray-500"></i>
                            </div>
                            <div class="absolute top-3 right-3">
                                <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full">
                                    Featured
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="mb-2">
                                <span class="text-sm text-gray-500">Skateboard • Vans</span>
                            </div>
                            
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <a href="/products/old-skool" class="hover:text-blue-600 transition-colors">
                                    Old Skool Classic
                                </a>
                            </h3>
                            
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-lg font-bold text-gray-900">
                                    Rp 850,000
                                </span>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    <span class="text-sm text-gray-600 ml-1">4.4</span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button onclick="addToCart(5)" class="flex-1 btn btn-primary text-sm">
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    Add to Cart
                                </button>
                                <a href="/products/old-skool" class="btn btn-outline text-sm px-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Product 6 -->
                    <div class="product-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="relative">
                            <div class="w-full h-64 bg-gradient-to-br from-pink-200 to-pink-300 flex items-center justify-center">
                                <i class="fas fa-shoe-prints text-4xl text-gray-500"></i>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="mb-2">
                                <span class="text-sm text-gray-500">Basketball • Adidas</span>
                            </div>
                            
                            <h3 class="font-semibold text-gray-900 mb-2">
                                <a href="/products/dame-8" class="hover:text-blue-600 transition-colors">
                                    Dame 8 Basketball
                                </a>
                            </h3>
                            
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-lg font-bold text-gray-900">
                                    Rp 1,950,000
                                </span>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    <span class="text-sm text-gray-600 ml-1">4.9</span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button onclick="addToCart(6)" class="flex-1 btn btn-primary text-sm">
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    Add to Cart
                                </button>
                                <a href="/products/dame-8" class="btn btn-outline text-sm px-3">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                            
                            <p class="text-xs text-orange-600 mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Only 3 left!
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <button class="px-3 py-2 text-gray-400 bg-white border border-gray-300 rounded-md">
                            Previous
                        </button>
                        <button class="px-3 py-2 bg-blue-600 text-white border border-blue-600 rounded-md">
                            1
                        </button>
                        <button class="px-3 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            2
                        </button>
                        <button class="px-3 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            3
                        </button>
                        <button class="px-3 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Next
                        </button>
                    </nav>
                </div>
            </main>
        </div>
    </div>
@endsection