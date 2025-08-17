<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Midtrans Configuration -->
    <meta name="midtrans-client-key" content="{{ config('services.midtrans.client_key') }}">
    <meta name="midtrans-production" content="{{ config('services.midtrans.is_production') ? 'true' : 'false' }}">
    @yield('head')
    <title>@yield('title', 'SneakerFlash - Premium Sneakers')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom Styles -->
    <style>
        /* Reset and Base Styles */
        * {
            box-sizing: border-box;
        }

        /* Exact Kick Avenue Colors - Background PUTIH */
        .ka-header {
            background: #ffffff;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .ka-logo {
            font-family: 'Arial Black', 'Helvetica', sans-serif;
            font-weight: 900;
            font-size: 24px;
            letter-spacing: 2px;
            color: #000000;
            text-decoration: none;
            font-style: italic;
            transform: skew(-10deg);
            display: inline-block;
        }

        /* Logo Image Styles */
        .ka-logo-img {
            height: 50px;
            width: auto;
            object-fit: contain;
        }
        
        /* Search container dengan lebar custom 1500px */
        .ka-search-custom {
            max-width: 1500px;
        }
        
        .ka-search-container {
            background: #f8f9fa;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .ka-search-input {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: transparent;
            padding: 12px 45px 12px 45px;
            width: 100%;
            font-size: 14px;
            color: #495057;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .ka-search-input:focus {
            outline: none !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        .ka-search-input::placeholder {
            color: #6c757d;
            font-weight: 400;
        }
        
        .ka-search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 14px;
            pointer-events: none;
        }
        
        .ka-search-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .ka-search-btn:hover {
            color: #495057;
        }
        
        .ka-auth-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .ka-login-btn {
            color: #666;
            border: 1px solid #ddd;
            background: white;
        }
        
        .ka-login-btn:hover {
            background: #f0f0f0;
            border-color: #ccc;
        }
        
        .ka-register-btn {
            background: #333;
            color: white;
            border: 1px solid #333;
        }
        
        .ka-register-btn:hover {
            background: #555;
        }

        /* Cart & Wishlist Icon Styles */
        .icon-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .icon-btn:hover {
            background: #e9ecef;
            color: #333;
            transform: translateY(-1px);
        }

        .icon-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            line-height: 1;
        }

        /* User Menu Button */
        .user-menu-btn {
            display: flex;
            align-items: center;
            color: #666;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            cursor: pointer;
            white-space: nowrap;
        }

        .user-menu-btn:hover {
            background: #e9ecef;
            color: #333;
        }

        /* User dropdown positioning */
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 180px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            margin-top: 10px;
            pointer-events: none;
        }

        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        /* Navigation dropdown styles */
        .nav-dropdown {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 200px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            margin-top: 10px;
            pointer-events: none;
        }

        .nav-dropdown.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        /* Navigation item container */
        .nav-item-container {
            position: relative;
            display: inline-block;
        }

        /* Main navigation button styling */
        .nav-main-btn {
            color: #666666;
            padding: 8px 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            font-family: 'Arial Black', 'Helvetica', sans-serif;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-align: center;
            position: relative;
            cursor: pointer;
            z-index: 1001;
            background: none;
            border: none;
        }
        
        .nav-main-btn:hover {
            background-color: rgba(0,0,0,0.05);
            color: #333;
        }
        
        .nav-main-btn.special {
            color: #ff4757 !important;
            font-weight: 600;
        }
        
        .nav-main-btn.special:hover {
            color: #ff6b7d !important;
        }

        /* Dropdown arrow */
        .nav-main-btn .dropdown-arrow {
            margin-left: 5px;
            font-size: 12px;
            transition: transform 0.2s ease;
        }

        .nav-main-btn.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        /* Simple link styling for items without dropdowns */
        .nav-simple-link {
            color: #666666;
            padding: 8px 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            font-family: 'Arial Black', 'Helvetica', sans-serif;
            transition: all 0.3s ease;
            display: block;
            text-align: center;
            position: relative;
            cursor: pointer;
            z-index: 1001;
        }
        
        .nav-simple-link:hover {
            background-color: rgba(0,0,0,0.05);
            color: #333;
        }
        
        .nav-simple-link.special {
            color: #ff4757 !important;
            font-weight: 600;
        }
        
        .nav-simple-link.special:hover {
            color: #ff6b7d !important;
        }

        .dropdown-item {
            display: block;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            width: 100%;
            text-align: left;
            background: none;
            border-left: none;
            border-right: none;
            border-top: none;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: #333;
        }

        .dropdown-header {
            padding: 15px 20px 10px;
            font-weight: 700;
            font-size: 14px;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 5px;
            font-family: 'Arial Black', 'Helvetica', sans-serif;
        }

        .dropdown-header-link {
            display: block;
            padding: 15px 20px 10px;
            font-weight: 700;
            font-size: 14px;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 5px;
            font-family: 'Arial Black', 'Helvetica', sans-serif;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .dropdown-header-link:hover {
            background: #f8f9fa;
            color: #000;
        }

        /* Image Carousel Styles */
        /* Image Carousel Styles - Updated with smaller height */
.carousel-container {
    position: relative;
    width: 100%;
    height: 180px; /* Reduced from 250px */
    overflow: hidden;
    background: #f8f9fa;
}

.carousel-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

.carousel-slide.active {
    opacity: 1;
}

.carousel-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 40px; /* Reduced from 50px */
    height: 40px; /* Reduced from 50px */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px; /* Reduced from 18px */
    color: #333;
    transition: all 0.3s ease;
    z-index: 10;
}

.carousel-nav:hover {
    background: rgba(255, 255, 255, 1);
    transform: translateY(-50%) scale(1.1);
}

.carousel-nav.prev {
    left: 15px; /* Reduced from 20px */
}

.carousel-nav.next {
    right: 15px; /* Reduced from 20px */
}

/* Mobile Styles */
@media (max-width: 768px) {
    .carousel-container {
        height: 140px; /* Reduced from 180px */
    }
    
    .carousel-nav {
        width: 35px; /* Reduced from 40px */
        height: 35px; /* Reduced from 40px */
        font-size: 14px; /* Reduced from 16px */
    }
    
    .carousel-nav.prev {
        left: 10px;
    }
    
    .carousel-nav.next {
        right: 10px;
    }
}

        /* Mobile Styles */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 9998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .mobile-menu-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 300px;
            height: 100vh;
            background: white;
            z-index: 9999;
            transition: left 0.3s ease;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .mobile-menu.open {
            left: 0;
        }

        .mobile-menu-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }

        .mobile-menu-item {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .mobile-menu-item:hover {
            background: #f8f9fa;
        }

        .mobile-menu-item.special {
            color: #ff4757;
            font-weight: 600;
        }

        .mobile-auth-buttons {
            padding: 20px;
            border-top: 1px solid #eee;
        }

        .mobile-auth-btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .mobile-login-btn {
            background: white;
            border: 2px solid #ddd;
            color: #666;
        }

        .mobile-register-btn {
            background: #333;
            border: 2px solid #333;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .carousel-container {
                height: 180px;
            }
            
            .carousel-nav {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .carousel-nav.prev {
                left: 10px;
            }
            
            .carousel-nav.next {
                right: 10px;
            }

            .ka-logo-img {
                height: 40px;
            }

            .icon-btn {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }

            .icon-badge {
                width: 18px;
                height: 18px;
                font-size: 10px;
                top: -3px;
                right: -3px;
            }
        }

        /* Loading spinner untuk button */
        .loading {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        function carousel() {
            return {
                currentSlide: 0,
                slides: [
                    { image: 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=1200&h=250&fit=crop', alt: 'Sneaker Collection 1' },
                    { image: 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=1200&h=250&fit=crop', alt: 'Sneaker Collection 2' },
                    { image: 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?w=1200&h=250&fit=crop', alt: 'Sneaker Collection 3' },
                    { image: 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?w=1200&h=250&fit=crop', alt: 'Sneaker Collection 4' }
                ],
                nextSlide() {
                    this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                },
                prevSlide() {
                    this.currentSlide = this.currentSlide === 0 ? this.slides.length - 1 : this.currentSlide - 1;
                },
                init() {
                    setInterval(() => {
                        this.nextSlide();
                    }, 5000);
                }
            }
        }

        // Navigation dropdown functionality
        function navigationDropdown() {
            return {
                activeDropdown: null,
                
                toggleDropdown(dropdownName) {
                    if (this.activeDropdown === dropdownName) {
                        this.activeDropdown = null;
                    } else {
                        this.activeDropdown = dropdownName;
                    }
                },
                
                closeDropdown() {
                    this.activeDropdown = null;
                },
                
                isDropdownActive(dropdownName) {
                    return this.activeDropdown === dropdownName;
                }
            }
        }

        // User dropdown functionality
        function userDropdown() {
            return {
                open: false,
                toggle() {
                    this.open = !this.open;
                },
                close() {
                    this.open = false;
                }
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(event) {
                // Close navigation dropdowns
                const navContainer = document.querySelector('.nav-container');
                if (navContainer && !navContainer.contains(event.target)) {
                    const alpineComponent = document.querySelector('[x-data*="navigationDropdown"]');
                    if (alpineComponent && alpineComponent._x_dataStack) {
                        alpineComponent._x_dataStack[0].closeDropdown();
                    }
                }
            });
        });

        // Function untuk update cart dan wishlist count
        function updateCartCount(count) {
            const cartBadge = document.getElementById('cartCount');
            if (cartBadge) {
                if (count > 0) {
                    cartBadge.textContent = count;
                    cartBadge.style.display = 'flex';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
        }

        function updateWishlistCount(count) {
            const wishlistBadge = document.getElementById('wishlistCount');
            if (wishlistBadge) {
                if (count > 0) {
                    wishlistBadge.textContent = count;
                    wishlistBadge.style.display = 'flex';
                } else {
                    wishlistBadge.style.display = 'none';
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header dengan navigation menu menyatu - background PUTIH -->
    <header class="ka-header sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay" :class="{ 'open': mobileMenuOpen }" @click="mobileMenuOpen = false"></div>
        
        <!-- Mobile Slide Menu -->
        <div class="mobile-menu" :class="{ 'open': mobileMenuOpen }">
            <!-- Menu Header -->
            <div class="mobile-menu-header">
                <img src="{{ asset('images/logo-sneakerflash.jpg') }}" alt="SneakerFlash Logo" class="ka-logo-img mx-auto">
            </div>
            
            <!-- Menu Items -->
            <div class="mobile-menu-content">
                <a href="/products?category=mens" class="mobile-menu-item">MENS</a>
                <a href="/products?category=womens" class="mobile-menu-item">WOMENS</a>
                <a href="/products?category=unisex" class="mobile-menu-item">UNISEX</a>
                <a href="/products?brands[]=Nike" class="mobile-menu-item">NIKE</a>
                <a href="/products?brands[]=Adidas" class="mobile-menu-item">ADIDAS</a>
                <a href="/products?category=accessories" class="mobile-menu-item">ACCESSORIES</a>
                <a href="/products?sale=true" class="mobile-menu-item special">SALE</a>
            </div>
            
            <!-- Auth Buttons -->
            <div class="mobile-auth-buttons">
                @auth
                    <a href="/orders" class="mobile-menu-item">
                        <i class="fas fa-shopping-bag mr-2"></i>My Orders
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="mobile-menu-item w-full text-left">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
                @else
                    <a href="/login" class="mobile-auth-btn mobile-login-btn">Login</a>
                    <a href="/register" class="mobile-auth-btn mobile-register-btn">Register</a>
                @endauth
            </div>
        </div>

        <!-- Baris pertama: Logo, Search, dan Auth -->
        <div class="max-w-full mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/">
                        <img src="{{ asset('images/logo-sneakerflash.jpg') }}" alt="SneakerFlash Logo" class="ka-logo-img">
                    </a>
                </div>

                <!-- Search Bar - hanya tampil di desktop -->
                <div class="hidden md:flex flex-1 max-w-2xl mx-8">
                    <form action="/products" method="GET" class="w-full ka-search-custom mx-auto">
                        <div class="ka-search-container">
                            <div class="relative flex items-center">
                                <i class="fas fa-search ka-search-icon"></i>
                                <input type="text" 
                                       name="search" 
                                       placeholder="Type any products here"
                                       value="{{ request('search') }}"
                                       class="ka-search-input flex-1">
                                <button type="submit" class="ka-search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- User Menu / Auth - UPDATED with Cart & Wishlist -->
                <div class="flex items-center space-x-3">
                    @auth
                        <!-- Wishlist Icon -->
                        <a href="{{ route('wishlist.index') }}" class="icon-btn" title="Wishlist">
                            <i class="fas fa-heart"></i>
                            @php
                                $wishlistCount = auth()->user()->getWishlistCount();
                            @endphp
                            @if($wishlistCount > 0)
                                <span class="icon-badge" id="wishlistCount">{{ $wishlistCount }}</span>
                            @else
                                <span class="icon-badge" id="wishlistCount" style="display: none;">0</span>
                            @endif
                        </a>

                        <!-- Cart Icon -->
                        <a href="{{ route('cart.index') }}" class="icon-btn" title="Shopping Cart">
                            <i class="fas fa-shopping-cart"></i>
                            @php
                                $cartCount = count(session('cart', []));
                            @endphp
                            @if($cartCount > 0)
                                <span class="icon-badge" id="cartCount">{{ $cartCount }}</span>
                            @else
                                <span class="icon-badge" id="cartCount" style="display: none;">0</span>
                            @endif
                        </a>

                        <!-- User Dropdown -->
                        <div class="relative" x-data="userDropdown()" @click.away="close()">
                            <button @click="toggle()" class="user-menu-btn">
                                <i class="fas fa-user-circle text-xl"></i>
                                <span class="hidden md:inline text-sm ml-2">{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down text-sm ml-1"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div class="user-dropdown" :class="{ 'show': open }">
                                <a href="{{ route('wishlist.index') }}" class="dropdown-item">
                                    <i class="fas fa-heart mr-2"></i>My Wishlist
                                    @if($wishlistCount > 0)
                                        <span class="ml-auto text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded-full">{{ $wishlistCount }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('orders.index') }}" class="dropdown-item">
                                    <i class="fas fa-shopping-bag mr-2"></i>My Orders
                                </a>
                                <a href="{{ route('profile.index') }}" class="dropdown-item">
                                    <i class="fas fa-user mr-2"></i>Profile
                                </a>
                                <div style="border-top: 1px solid #f0f0f0; margin: 5px 0;"></div>
                                <form action="{{ route('logout') }}" method="POST" class="block">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <!-- Login/Register untuk guest -->
                        <a href="{{ route('login') }}" class="ka-auth-btn ka-login-btn">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="ka-auth-btn ka-register-btn">
                            Register
                        </a>
                    @endauth
                </div>
                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-600 hover:text-gray-800 ml-3">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Baris kedua: Navigation Menu dengan Dropdown -->
        <div class="max-w-full px-4">
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center justify-center py-0 nav-container" x-data="navigationDropdown()" @click.away="closeDropdown()">
                
                <!-- MENS Dropdown -->
                <div class="nav-item-container">
                    <button @click="toggleDropdown('mens')" 
                            class="nav-main-btn" 
                            :class="{ 'active': isDropdownActive('mens') }">
                        MENS
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="nav-dropdown" :class="{ 'show': isDropdownActive('mens') }">
                        <!-- FOOTWEAR Section -->
                        <a href="/products?category=mens&section=footwear" class="dropdown-header-link">FOOTWEAR</a>
                        <a href="/products?category=mens&type=lifestyle" class="dropdown-item">Lifestyle/Casual</a>
                        <a href="/products?category=mens&type=running" class="dropdown-item">Running</a>
                        <a href="/products?category=mens&type=training" class="dropdown-item">Training</a>
                        <a href="/products?category=mens&type=basketball" class="dropdown-item">Basketball</a>
                        
                        <!-- APPAREL Section -->
                        <a href="/products?category=mens&type=apparel" class="dropdown-header-link">APPAREL</a>
                    </div>
                </div>

                <!-- WOMENS Dropdown -->
                <div class="nav-item-container">
                    <button @click="toggleDropdown('womens')" 
                            class="nav-main-btn" 
                            :class="{ 'active': isDropdownActive('womens') }">
                        WOMENS
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="nav-dropdown" :class="{ 'show': isDropdownActive('womens') }">
                        <!-- FOOTWEAR Section -->
                        <a href="/products?category=womens&section=footwear" class="dropdown-header-link">FOOTWEAR</a>
                        <a href="/products?category=womens&type=lifestyle" class="dropdown-item">Lifestyle/Casual</a>
                        <a href="/products?category=womens&type=running" class="dropdown-item">Running</a>
                        <a href="/products?category=womens&type=training" class="dropdown-item">Training</a>
                        
                        <!-- APPAREL Section -->
                        <a href="/products?category=womens&type=apparel" class="dropdown-header-link">APPAREL</a>
                    </div>
                </div>

                <!-- UNISEX (Updated from KIDS) -->
                <div class="nav-item-container">
                    <button @click="toggleDropdown('unisex')" 
                            class="nav-main-btn" 
                            :class="{ 'active': isDropdownActive('unisex') }">
                        UNISEX
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="nav-dropdown" :class="{ 'show': isDropdownActive('unisex') }">
                        <!-- FOOTWEAR Section -->
                        <a href="/products?category=unisex&section=footwear" class="dropdown-header-link">FOOTWEAR</a>
                        <a href="/products?category=unisex&type=lifestyle" class="dropdown-item">Lifestyle/Casual</a>
                        <a href="/products?category=unisex&type=running" class="dropdown-item">Running</a>
                        <a href="/products?category=unisex&type=training" class="dropdown-item">Training</a>
                        <a href="/products?category=unisex&type=basketball" class="dropdown-item">Basketball</a>
                        
                        <!-- APPAREL Section -->
                        <a href="/products?category=unisex&type=apparel" class="dropdown-header-link">APPAREL</a>
                    </div>
                </div>

                <!-- BRAND Dropdown -->
                <div class="nav-item-container">
                    <button @click="toggleDropdown('brand')" 
                            class="nav-main-btn" 
                            :class="{ 'active': isDropdownActive('brand') }">
                        BRAND
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="nav-dropdown" :class="{ 'show': isDropdownActive('brand') }">
                        <a href="/products?brands[]=Nike" class="dropdown-item">
                            <i class="fab fa-nike mr-2"></i>NIKE
                        </a>
                        <a href="/products?brands[]=Adidas" class="dropdown-item">
                            <i class="fab fa-adidas mr-2"></i>ADIDAS
                        </a>
                        <a href="/products?brands[]=Puma" class="dropdown-item">
                            <i class="fab fa-puma mr-2"></i>PUMA
                        </a>
                        <a href="/products?brands[]=Converse" class="dropdown-item">
                            <i class="fas fa-star mr-2"></i>CONVERSE
                        </a>
                        <a href="/products?brands[]=Vans" class="dropdown-item">
                            <i class="fas fa-skateboard mr-2"></i>VANS
                        </a>
                        <a href="/products?brands[]=New Balance" class="dropdown-item">
                            <i class="fas fa-balance-scale mr-2"></i>NEW BALANCE
                        </a>
                        <a href="/products?brands[]=Jordan" class="dropdown-item">
                            <i class="fas fa-basketball-ball mr-2"></i>JORDAN
                        </a>
                        <a href="/products?brands[]=Reebok" class="dropdown-item">
                            <i class="fas fa-running mr-2"></i>REEBOK
                        </a>
                        <a href="/products?brands[]=ASICS" class="dropdown-item">
                            <i class="fas fa-shoe-prints mr-2"></i>ASICS
                        </a>
                        <a href="/products?brands[]=Under Armour" class="dropdown-item">
                            <i class="fas fa-shield-alt mr-2"></i>UNDER ARMOUR
                        </a>
                        <a href="/products?brands[]=Skechers" class="dropdown-item">
                            <i class="fas fa-walking mr-2"></i>SKECHERS
                        </a>
                        <a href="/products?brands[]=Fila" class="dropdown-item">
                            <i class="fas fa-mountain mr-2"></i>FILA
                        </a>
                        <a href="/products?brands[]=DC Shoes" class="dropdown-item">
                            <i class="fas fa-skateboard mr-2"></i>DC SHOES
                        </a>
                        <a href="/products?brands[]=Timberland" class="dropdown-item">
                            <i class="fas fa-tree mr-2"></i>TIMBERLAND
                        </a>
                    </div>
                </div>

                <!-- ACCESSORIES Dropdown (Updated with new product types) -->
                <div class="nav-item-container">
                    <button @click="toggleDropdown('accessories')" 
                            class="nav-main-btn" 
                            :class="{ 'active': isDropdownActive('accessories') }">
                        ACCESSORIES
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="nav-dropdown" :class="{ 'show': isDropdownActive('accessories') }">
                        <a href="/products?type=accessories" class="dropdown-header-link">ALL ACCESSORIES</a>
                        <a href="/products?type=bags" class="dropdown-item">
                            <i class="fas fa-shopping-bag mr-2"></i>Bags
                        </a>
                        <a href="/products?type=caps" class="dropdown-item">
                            <i class="fas fa-hat-cowboy mr-2"></i>Caps & Hats
                        </a>
                        <a href="/products?type=apparel" class="dropdown-item">
                            <i class="fas fa-tshirt mr-2"></i>Apparel
                        </a>
                        <a href="/products?category=accessories&type=cleaner" class="dropdown-item">
                            <i class="fas fa-spray-can mr-2"></i>Shoe Care
                        </a>
                    </div>
                </div>

                <!-- SALE -->
                <div class="nav-item-container">
                    <a href="/products?sale=true" class="nav-simple-link special">
                        SALE
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Search (tampil di mobile saja) -->
    <div class="md:hidden bg-white border-b px-4 py-3">
        <form action="/products" method="GET">
            <div class="ka-search-container">
                <div class="relative flex items-center">
                    <i class="fas fa-search ka-search-icon"></i>
                    <input type="text" 
                           name="search" 
                           placeholder="Type any products here"
                           value="{{ request('search') }}"
                           class="ka-search-input flex-1">
                    <button type="submit" class="ka-search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Mobile Cart & Wishlist (tampil di mobile) -->
    <div class="md:hidden bg-white border-b px-4 py-3">
    @auth
        <div class="flex justify-center space-x-6">
            <!-- Mobile Wishlist -->
            <a href="{{ route('wishlist.index') }}" class="flex items-center space-x-2 text-gray-600 hover:text-gray-800">
                <div class="relative">
                    <i class="fas fa-heart text-lg"></i>
                    @if($wishlistCount > 0)
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                            {{ $wishlistCount }}
                        </span>
                    @endif
                </div>
                <span class="text-sm font-medium">Wishlist</span>
            </a>

            <!-- Mobile Cart -->
            <a href="{{ route('cart.index') }}" class="flex items-center space-x-2 text-gray-600 hover:text-gray-800">
                <div class="relative">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    @if($cartCount > 0)
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                            {{ $cartCount }}
                        </span>
                    @endif
                </div>
                <span class="text-sm font-medium">Cart</span>
            </a>
        </div>
    @endauth
</div>

    <!-- Image Carousel Slider -->
    <div class="carousel-container" x-data="carousel()">
        <!-- Carousel Slides -->
        <template x-for="(slide, index) in slides" :key="index">
            <div class="carousel-slide" :class="{ 'active': currentSlide === index }">
                <img :src="slide.image" :alt="slide.alt" />
            </div>
        </template>

        <!-- Navigation Arrows -->
        <button class="carousel-nav prev" @click="prevSlide()">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="carousel-nav next" @click="nextSlide()">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mx-4 mt-4" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mx-4 mt-4" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mx-4 mt-4" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span>{{ session('warning') }}</span>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <img src="{{ asset('images/logo-sneakerflash.jpg') }}" alt="SneakerFlash Logo" class="ka-logo-img mb-4 filter brightness-0 invert">
                    <p class="text-gray-400 text-sm">
                        Premium sneakers and streetwear for everyone. Authentic products, fast delivery.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/products" class="text-gray-400 hover:text-white">All Products</a></li>
                        <li><a href="/products?sale=true" class="text-gray-400 hover:text-white">Sale</a></li>
                        <li><a href="/about" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="/contact" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="font-semibold mb-4">Customer Service</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/shipping-info" class="text-gray-400 hover:text-white">Shipping Info</a></li>
                        <li><a href="/returns" class="text-gray-400 hover:text-white">Returns</a></li>
                        <li><a href="/size-guide" class="text-gray-400 hover:text-white">Size Guide</a></li>
                        <li><a href="/terms" class="text-gray-400 hover:text-white">Terms & Conditions</a></li>
                    </ul>
                </div>

                <!-- Follow Us -->
                <div>
                    <h4 class="font-semibold mb-4">Follow Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-tiktok text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} SneakerFlash. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>