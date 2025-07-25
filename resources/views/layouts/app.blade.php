{{-- Replace: resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SneakerFlash - Premium Sneakers Store')</title>
    <meta name="description" content="@yield('description', 'Discover the latest and greatest sneakers at SneakerFlash. Premium quality, authentic brands, fast delivery.')">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        [x-cloak] { display: none !important; }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }
        
        .badge-primary { @apply bg-blue-100 text-blue-800; }
        .badge-success { @apply bg-green-100 text-green-800; }
        .badge-warning { @apply bg-yellow-100 text-yellow-800; }
        .badge-danger { @apply bg-red-100 text-red-800; }
        
        .btn {
            @apply inline-flex items-center justify-center px-4 py-2 border font-medium rounded-md transition-colors duration-200;
        }
        
        .btn-primary {
            @apply bg-blue-600 border-blue-600 text-white hover:bg-blue-700 hover:border-blue-700;
        }
        
        .btn-secondary {
            @apply bg-gray-600 border-gray-600 text-white hover:bg-gray-700 hover:border-gray-700;
        }
        
        .btn-outline {
            @apply bg-transparent border-gray-300 text-gray-700 hover:bg-gray-50;
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50" x-data="{ mobileMenuOpen: false, cartCount: 0 }">
        <!-- Top Bar -->
        <div class="bg-gray-900 text-white text-sm">
            <div class="container mx-auto px-4 py-2">
                <div class="flex justify-between items-center">
                    <div class="flex space-x-4">
                        <span><i class="fas fa-phone mr-1"></i> +62 21 1234 5678</span>
                        <span><i class="fas fa-envelope mr-1"></i> hello@sneakerflash.com</span>
                    </div>
                    <div class="flex space-x-4">
                        @guest
                            <a href="/login" class="hover:text-blue-300 transition-colors">Login</a>
                            <a href="/register" class="hover:text-blue-300 transition-colors">Register</a>
                        @else
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="hover:text-blue-300 transition-colors">
                                    <i class="fas fa-user mr-1"></i> {{ auth()->user()->name }}
                                    <i class="fas fa-chevron-down ml-1 text-xs"></i>
                                </button>
                                <div x-show="open" @click.away="open = false" x-cloak
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                    <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                    <a href="/orders" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Orders</a>
                                    <form method="POST" action="/logout" class="block">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</button>
                                    </form>
                                </div>
                            </div>
                        @endguest
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="/" class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-bolt text-blue-600"></i>
                        SneakerFlash
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="hidden md:flex flex-1 max-w-lg mx-8">
                    <form action="/products" method="GET" class="w-full">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search for sneakers..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   value="{{ request('search') }}">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Cart & Mobile Menu -->
                <div class="flex items-center space-x-4">
                    <!-- Cart -->
                    <a href="/cart" class="relative p-2 text-gray-600 hover:text-blue-600 transition-colors">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span x-show="cartCount > 0" x-text="cartCount" x-cloak
                              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"></span>
                    </a>

                    <!-- Mobile Menu Button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-gray-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="bg-gray-100 border-t">
            <div class="container mx-auto px-4">
                <!-- Desktop Navigation -->
                <div class="hidden md:flex space-x-8 py-4">
                    <a href="/" class="text-gray-700 hover:text-blue-600 font-medium transition-colors {{ request()->is('/') ? 'text-blue-600' : '' }}">Home</a>
                    <a href="/products" class="text-gray-700 hover:text-blue-600 font-medium transition-colors {{ request()->is('products*') ? 'text-blue-600' : '' }}">All Products</a>
                    <a href="/categories/basketball-shoes" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Basketball</a>
                    <a href="/categories/casual-sneakers" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Casual</a>
                    <a href="/products?featured=1" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Featured</a>
                    <a href="/products?sort=price_low" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Sale</a>
                </div>

                <!-- Mobile Navigation -->
                <div x-show="mobileMenuOpen" x-cloak class="md:hidden py-4 space-y-2">
                    <a href="/" class="block py-2 text-gray-700 hover:text-blue-600 transition-colors">Home</a>
                    <a href="/products" class="block py-2 text-gray-700 hover:text-blue-600 transition-colors">All Products</a>
                    <a href="/categories/basketball-shoes" class="block py-2 text-gray-700 hover:text-blue-600 transition-colors">Basketball</a>
                    <a href="/categories/casual-sneakers" class="block py-2 text-gray-700 hover:text-blue-600 transition-colors">Casual</a>
                    <a href="/products?featured=1" class="block py-2 text-gray-700 hover:text-blue-600 transition-colors">Featured</a>
                    
                    <!-- Mobile Search -->
                    <div class="pt-4">
                        <form action="/products" method="GET">
                            <input type="text" name="search" placeholder="Search..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                   value="{{ request('search') }}">
                        </form>
                    </div>
                </div>
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
    <footer class="bg-gray-900 text-white mt-16">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <h3 class="text-xl font-bold mb-4">
                        <i class="fas fa-bolt text-blue-400"></i>
                        SneakerFlash
                    </h3>
                    <p class="text-gray-300 mb-4">
                        Your premium destination for authentic sneakers. Fast delivery, quality guaranteed.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white transition-colors"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-300 hover:text-white transition-colors"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="/products" class="text-gray-300 hover:text-white transition-colors">All Products</a></li>
                        <li><a href="/categories/basketball-shoes" class="text-gray-300 hover:text-white transition-colors">Basketball</a></li>
                        <li><a href="/categories/casual-sneakers" class="text-gray-300 hover:text-white transition-colors">Casual</a></li>
                        <li><a href="/products?featured=1" class="text-gray-300 hover:text-white transition-colors">Featured</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="font-semibold mb-4">Customer Service</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Shipping Info</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Returns</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Size Guide</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">FAQ</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="font-semibold mb-4">Contact Info</h4>
                    <div class="space-y-3 text-gray-300">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-3 text-blue-400"></i>
                            <span>Jakarta, Indonesia</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-phone mr-3 text-blue-400"></i>
                            <span>+62 21 1234 5678</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-blue-400"></i>
                            <span>hello@sneakerflash.com</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock mr-3 text-blue-400"></i>
                            <span>Mon - Fri: 9AM - 6PM</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-gray-700 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-300 text-sm">
                    © {{ date('Y') }} SneakerFlash. All rights reserved.
                </p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="#" class="text-gray-300 hover:text-white text-sm transition-colors">Privacy Policy</a>
                    <a href="#" class="text-gray-300 hover:text-white text-sm transition-colors">Terms of Service</a>
                    <a href="#" class="text-gray-300 hover:text-white text-sm transition-colors">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Cart functionality (basic for now)
        function addToCart(productId, size = null, color = null) {
            const quantity = document.getElementById('quantity')?.value || 1;
            
            // Simple alert for now
            alert('Product added to cart!');
            
            // You can implement AJAX call here later
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-md shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            }`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
    
    @stack('scripts')
</body>
</html>