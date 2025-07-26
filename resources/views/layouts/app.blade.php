<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SneakerFlash - Premium Sneakers')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS CDN (sementara, ganti vite) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- HAPUS BARIS INI JIKA ADA: -->
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <nav class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-running text-white"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-900">SneakerFlash</span>
                    </a>
                </div>

                <!-- Search Bar (Desktop) -->
                <div class="hidden md:flex flex-1 max-w-lg mx-8">
                    <form action="{{ route('products.index') }}" method="GET" class="w-full">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search for sneakers..."
                                   value="{{ request('search') }}"
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-6">
                    <!-- Navigation Links -->
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Home</a>
                    <a href="{{ route('products.index') }}" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Products</a>
                    <a href="/categories/basketball-shoes" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Basketball</a>
                    <a href="/categories/casual-shoes" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Casual</a>

                    <!-- Cart -->
                    <a href="{{ route('cart.index') }}" class="relative text-gray-700 hover:text-blue-600 transition-colors">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            0
                        </span>
                    </a>

                    <!-- Auth Links -->
                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-colors">
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <span class="font-medium">{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down text-sm"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open" @click.away="open = false" 
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="/orders" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-shopping-bag mr-2"></i>My Orders
                                </a>
                                <hr class="my-1">
                                <form action="{{ route('logout') }}" method="POST" class="block">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <!-- Login/Register for guests -->
                        <div class="flex items-center space-x-4">
                            <a href="/login" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">
                                <i class="fas fa-sign-in-alt mr-1"></i>Login
                            </a>
                            <a href="/register" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                Sign Up
                            </a>
                        </div>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-700 hover:text-blue-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen = false" class="md:hidden py-4 space-y-2">
                <a href="{{ route('home') }}" class="block py-2 px-4 text-gray-700 hover:text-blue-600 transition-colors">Home</a>
                <a href="{{ route('products.index') }}" class="block py-2 px-4 text-gray-700 hover:text-blue-600 transition-colors">Products</a>
                <a href="{{ route('cart.index') }}" class="block py-2 px-4 text-gray-700 hover:text-blue-600 transition-colors">
                    <i class="fas fa-shopping-cart mr-2"></i>Cart
                </a>
            </div>
        </nav>
    </header>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3" x-data="{ show: true }" x-show="show">
            <div class="container mx-auto flex justify-between items-center">
                <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
                <button @click="show = false" class="text-green-700 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3" x-data="{ show: true }" x-show="show">
            <div class="container mx-auto flex justify-between items-center">
                <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
                <button @click="show = false" class="text-red-700 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-running text-white"></i>
                        </div>
                        <span class="text-xl font-bold">SneakerFlash</span>
                    </div>
                    <p class="text-gray-400">Your destination for premium sneakers and athletic footwear.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('products.index') }}" class="hover:text-white transition-colors">All Products</a></li>
                        <li><a href="/categories/running-shoes" class="hover:text-white transition-colors">Running</a></li>
                        <li><a href="/categories/basketball-shoes" class="hover:text-white transition-colors">Basketball</a></li>
                        <li><a href="/categories/casual-shoes" class="hover:text-white transition-colors">Casual</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Customer Service</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Shipping Info</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Returns</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Size Guide</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-4">Connect With Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 SneakerFlash. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('scripts')
</body>
</html>