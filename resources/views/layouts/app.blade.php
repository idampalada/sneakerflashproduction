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
    
    <style>
    <!-- Custom Styles -->
    /* Reset and Base Styles */
* {
    box-sizing: border-box;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

/* Exact Kick Avenue Colors - Background PUTIH */
.ka-header {
    background: #ffffff;
    border-bottom: 1px solid #e5e5e5;
}

.ka-logo {
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    color: #000000;  /* GANTI DARI #666666 ke #000000 (HITAM) */
    padding: 8px 20px;
    text-decoration: none;
    font-weight: 700;  /* TETAP BOLD */
    font-size: 16px;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    color: #000000;  /* GANTI DARI #333 ke #000000 (HITAM SAAT HOVER) */
}

.nav-main-btn.special {
    color: #ff4757 !important;
    font-weight: 700;
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
    color: #000000;  /* GANTI DARI #666666 ke #000000 (HITAM) */
    padding: 8px 20px;
    text-decoration: none;
    font-weight: 700;  /* TETAP BOLD */
    font-size: 16px;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    transition: all 0.3s ease;
    display: block;
    text-align: center;
    position: relative;
    cursor: pointer;
    z-index: 1001;
}

.nav-simple-link:hover {
    background-color: rgba(0,0,0,0.05);
    color: #000000;  /* GANTI DARI #333 ke #000000 (HITAM SAAT HOVER) */
}

.nav-simple-link.special {
    color: #ff4757 !important;
    font-weight: 700;
}

.nav-simple-link.special:hover {
    color: #ff6b7d !important;
}

.dropdown-item {
    display: block;
    padding: 12px 20px;
    color: #666;
    text-decoration: none;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    text-decoration: none;
    transition: all 0.3s ease;
}

.dropdown-header-link:hover {
    background: #f8f9fa;
    color: #000;
}

/* Image Carousel Styles - Updated with smaller height */
/* === iBox-like Hero Banner (full-bleed) === */
/* Full-bleed seperti iBox */
.carousel-wrapper{
 position:relative;
 left:50%; right:50%;
 margin-left:-50vw; margin-right:-50vw;
 width:100vw;
 margin-bottom:2rem;
}

/* Tinggi fix (desktop=550) */
.carousel-container{
 position:relative; width:100%;
 height:550px;                       /* iBox desktop */
 overflow:hidden;
 background:transparent;               /* sama seperti iBox */
 display:flex; align-items:center; justify-content:center;
 padding-bottom:50px;                 /* Tambahkan ruang untuk dots */
}

/* Slide */
.carousel-slide{ position:absolute; inset:0; opacity:0; transition:opacity .5s; }
.carousel-slide.active{ opacity:1; }

/* KUNCI: jangan dicrop, penuhi area setinggi 550, center */
.carousel-slide img{
 display:block;
 width:100%; height:100% !important; /* penting: kalahkan img{height:auto} global */
 object-fit:contain;                  /* biar tidak zoom/crop */
 object-position:center;
}

/* panah & dots (boleh tetap) */
.carousel-nav{
 position:absolute; top:50%; transform:translateY(-50%);
 background:rgba(255,255,255,.9); border:none;
 width:48px; height:48px; border-radius:9999px;
 display:flex; align-items:center; justify-content:center;
 cursor:pointer; font-size:18px; color:#333; z-index:10;
 box-shadow:0 2px 8px rgba(0,0,0,.15);
}
.carousel-nav:hover{ background:#fff; transform:translateY(-50%) scale(1.06); }
.carousel-nav.prev{ left:24px; } .carousel-nav.next{ right:24px; }

.carousel-indicators{
 position:absolute; left:50%; transform:translateX(-50%);
 bottom:15px; display:flex; gap:8px; z-index:10;
}
.carousel-dot{
 width:10px; height:10px; border-radius:9999px; border:none;
 background:rgba(255,255,255,.5); transition:all .2s; cursor:pointer;
}
.carousel-dot.active{ background:#fff; transform:scale(1.15); }

/* Tablet & Mobile heights */
@media screen and (min-width:768px) and (max-width:1023px){ 
 .carousel-container{ height:430px !important; } 
}

@media screen and (max-width:767px){
 .carousel-container{ 
   height:370px !important; 
   min-height:370px;
   padding-bottom:40px;               /* Tambahkan ruang dots mobile */
 }
 .carousel-nav{ width:40px; height:40px; font-size:16px; }
 .carousel-nav.prev{ left:14px; } .carousel-nav.next{ right:14px; }
 .carousel-indicators{ bottom:10px; gap:6px; }
 .carousel-dot{ width:8px; height:8px; }
}

/* Hindari scrollbar horizontal dari full-bleed */
html, body { 
    overflow-x: hidden; 
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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

/* Hilangkan spacing antara search dan banner seperti iBox */
.md\:hidden.bg-white.border-b {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

.carousel-wrapper {
    margin-top: 0 !important;
}

/* Khusus untuk mobile cart & wishlist section juga */
.md\:hidden.bg-white.border-b.px-4.py-3:last-of-type {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

/* Override spacing yang mungkin ada */
@media (max-width: 767px) {
    .md\:hidden {
        margin-bottom: 0 !important;
    }
    
    .carousel-wrapper {
        margin-top: 0 !important;
    }
}

/* Hilangkan spacing dan border pada mobile search */
.md\:hidden.bg-white.border-b.px-4.py-3 {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
    border-bottom: none !important; /* Hilangkan garis border */
}

/* Hilangkan spacing pada mobile cart & wishlist jika ada */
.md\:hidden.bg-white.border-b.px-4.py-3:last-of-type {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
    border-bottom: none !important;
}

/* Pastikan carousel langsung menempel */
.carousel-wrapper {
    margin-top: 0 !important;
}

/* Override semua border-b pada mobile sections */
@media (max-width: 767px) {
    .md\:hidden.border-b {
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    
    .carousel-wrapper {
        margin-top: 0 !important;
    }
}

/* Mobile Bottom Navigation Styles */
.mobile-bottom-nav {
    height: 70px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.mobile-bottom-nav a {
    transition: color 0.2s ease;
    min-width: 60px;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

.mobile-bottom-nav a:hover {
    color: #2563eb;
}

/* Add padding to body to prevent content being hidden behind bottom nav */
@media (max-width: 767px) {
    body {
        padding-bottom: 70px;
    }
}

/* Active state styling */
.mobile-bottom-nav a.text-blue-600 i {
    transform: scale(1.1);
}

/* Cart badge animation */
.mobile-bottom-nav .bg-red-500 {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}
    </style>

    <script>
        function carousel() {
    return {
        currentSlide: 0,
        slides: [
            { 
              image: 'https://ibox.co.id/_next/image?url=https%3A%2F%2Fstorage.googleapis.com%2Feraspacelink%2Fpmp%2Fproduction%2Fbanners%2Fimages%2FjFvohNn3y1zzxdl6qtkYvDZSJHzuCFebM5uvIS2x.jpg&w=1920&q=85', 
              alt: 'Sneaker Collection 1',
              bg: '#000000' // background hitam
            },
            { 
              image: 'https://ibox.co.id/_next/image?url=https%3A%2F%2Fstorage.googleapis.com%2Feraspacelink%2Fpmp%2Fproduction%2Fbanners%2Fimages%2FxFb3N6fjOOjG3kKfh3HTpol6AZPajfpDCv9k8eCd.webp&w=1920&q=85', 
              alt: 'Sneaker Collection 2',
              bg: '#ffffffff' // abu muda
            },
            { 
              image: 'https://ibox.co.id/_next/image?url=https%3A%2F%2Fstorage.googleapis.com%2Feraspacelink%2Fpmp%2Fproduction%2Fbanners%2Fimages%2F5bSAF4TEXHOtoyWIhIGmS7JNiIJeGlrAy5dFxw4r.webp&w=1920&q=85', 
              alt: 'Sneaker Collection 3',
              bg: '#ffffffff' // putih
            },
            { 
              image: 'https://ibox.co.id/_next/image?url=https%3A%2F%2Fstorage.googleapis.com%2Feraspacelink%2Fpmp%2Fproduction%2Fbanners%2Fimages%2FM2g1EP6xGtyehbWO5jHCWQTSBXASk36dgp79OtNJ.webp&w=1920&q=85', 
              alt: 'Sneaker Collection 4',
              bg: '#CFEAF6' // abu gelap
            }
        ],
        autoPlay: true,
        autoPlayInterval: 5000,
        init() {
            if (this.autoPlay) {
                setInterval(() => { this.nextSlide(); }, this.autoPlayInterval);
            }
        },
        nextSlide() {
            this.currentSlide = (this.currentSlide + 1) % this.slides.length;
        },
        prevSlide() {
            this.currentSlide = this.currentSlide === 0 ? this.slides.length - 1 : this.currentSlide - 1;
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
<div class="carousel-wrapper">
  <div class="carousel-container" x-data="carousel()" x-init="init()">
<template x-for="(slide, index) in slides" :key="index">
  <div class="carousel-slide"
       :class="{ 'active': currentSlide === index }"
       :style="slide.bg ? { backgroundColor: slide.bg } : {}">
    <img :src="slide.image" :alt="slide.alt" loading="lazy">
  </div>
</template>


    <button class="carousel-nav prev" @click="prevSlide()" aria-label="Previous">
      <i class="fas fa-chevron-left"></i>
    </button>
    <button class="carousel-nav next" @click="nextSlide()" aria-label="Next">
      <i class="fas fa-chevron-right"></i>
    </button>

    <div class="carousel-indicators">
      <template x-for="(slide, index) in slides" :key="'dot-'+index">
        <button class="carousel-dot" :class="{ 'active': currentSlide === index }"
                @click="currentSlide = index" :aria-label="'Go to slide ' + (index + 1)"></button>
      </template>
    </div>
  </div>
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
    <footer class="bg-black text-white py-12" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <!-- Container tanpa max-width constraint untuk logo -->
    <div class="w-full">
        <!-- Grid Layout: 2 kolom di mobile (tanpa logo), 3 kolom di desktop -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 md:gap-8">
            
            <!-- Company Info - BENAR-BENAR di pojok kiri tanpa margin/padding, hidden di mobile -->
            <div class="hidden md:block md:col-span-1">
                <div class="pl-4 lg:pl-8">
                    <img src="{{ asset('images/logo-sneakerflash.jpg') }}" alt="SneakerFlash Logo" class="ka-logo-img mb-4 filter brightness-0 invert">
                    <p class="text-gray-300 text-sm pr-4">
                        Premium sneakers and streetwear for everyone. Authentic products, fast delivery.
                    </p>
                </div>
            </div>

            <!-- Section 1: Information -->
            <div class="px-4 lg:px-0 group">
                <h4 class="font-semibold mb-2 text-white">Information</h4>
                <div class="w-8 h-px bg-gray-400 mb-4 transition-all duration-300 group-hover:w-40"></div>
                <ul class="space-y-2 text-sm">
                    <li><a href="/about" class="text-gray-300 hover:text-white flex items-center"><span class="mr-2">></span>About Us</a></li>
                    <li><a href="/delivery" class="text-gray-300 hover:text-white flex items-center"><span class="mr-2">></span>Delivery</a></li>
                    <li><a href="/terms" class="text-gray-300 hover:text-white flex items-center"><span class="mr-2">></span>Terms & Conditions</a></li>
                    <li><a href="/privacy" class="text-gray-300 hover:text-white flex items-center"><span class="mr-2">></span>Privacy Policy</a></li>
                    <li><a href="/flash-club" class="text-gray-300 hover:text-white flex items-center"><span class="mr-2">></span>Flash Club</a></li>
                </ul>
            </div>

<div class="px-2 lg:px-0 group">
    <h4 class="font-semibold mb-2 text-white">Contact Us</h4>
    <div class="w-8 h-px bg-gray-400 mb-4 transition-all duration-300 group-hover:w-20"></div>
    <div class="space-y-3 text-sm pr-2">
        <!-- Location - SEKARANG RATA -->
        <div class="flex items-center space-x-2">
            <i class="fas fa-map-marker-alt text-gray-300 flex-shrink-0 text-xs w-3 text-center"></i>
            <span class="text-gray-300 leading-tight">West Jakarta, Indonesia</span>
        </div>
        
        <!-- Phone - TETAP RATA -->
        <div class="flex items-center space-x-2">
            <i class="fas fa-phone text-gray-300 flex-shrink-0 text-xs w-3 text-center"></i>
            <a href="tel:0812345678" class="text-gray-300 hover:text-white">0812345678</a>
        </div>
        
        <!-- Email - SEKARANG RATA -->
        <div class="flex items-center space-x-2">
            <i class="fas fa-envelope text-gray-300 flex-shrink-0 text-xs w-3 text-center"></i>
            <div class="min-w-0 flex-1">
                <a href="mailto:hello@sneakersflash.com" class="text-gray-300 hover:text-white leading-tight break-all text-xs sm:text-sm">hello@sneakersflash.com</a>
            </div>
        </div>
    </div>
</div>
</div>


        <!-- Social Media Icons -->
        <div class="border-t border-gray-700 mt-8 pt-8 text-center">
            <div class="max-w-7xl mx-auto px-4 lg:px-8">
                <div class="flex justify-center space-x-6">
                    <a href="https://instagram.com/sneakersflash" target="_blank" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fab fa-instagram text-2xl"></i>
                    </a>
                    <a href="https://tiktok.com/@sneakersflash" target="_blank" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fab fa-tiktok text-2xl"></i>
                    </a>
                    <a href="https://facebook.com/sneakersflash" target="_blank" class="text-gray-300 hover:text-white transition-colors">
                        <i class="fab fa-facebook text-2xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Alternative: Jika masih belum pojok banget, pakai ini -->
<style>
/* Opsi ekstrem: Logo benar-benar di pojok kiri tanpa padding */
.logo-absolute-left {
    margin-left: -1rem !important;
    padding-left: 1rem !important;
}

/* Atau gunakan negative margin */
.logo-negative-margin {
    margin-left: -2rem;
}
</style>
    <!-- Mobile Bottom Navigation - Add this to your main layout file (layouts/app.blade.php) before closing body tag -->
<!-- Mobile Bottom Navigation - Updated dengan warna hitam bold -->
<div class="mobile-bottom-nav fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50 md:hidden">
    <div class="flex items-center justify-around py-2">
        <!-- Home -->
        <a href="/" class="flex flex-col items-center py-2 px-4 {{ request()->is('/') ? 'text-black font-bold' : 'text-gray-400' }}">
            <div class="relative">
                <i class="fas fa-home text-xl mb-1"></i>
            </div>
            <span class="text-xs font-medium">Home</span>
        </a>

        <!-- Cart -->
        <a href="{{ route('cart.index') }}" class="flex flex-col items-center py-2 px-4 {{ request()->is('cart*') ? 'text-black font-bold' : 'text-gray-400' }}">
            <div class="relative">
                <i class="fas fa-shopping-cart text-xl mb-1"></i>
                @if(isset($cartCount) && $cartCount > 0)
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                        {{ $cartCount > 99 ? '99+' : $cartCount }}
                    </span>
                @endif
            </div>
            <span class="text-xs font-medium">Cart</span>
        </a>

        <!-- Account/Profile -->
        @auth
            <a href="{{ route('profile.index') }}" class="flex flex-col items-center py-2 px-4 {{ request()->is('profile*') || request()->is('account*') ? 'text-black font-bold' : 'text-gray-400' }}">
                <div class="relative">
                    <i class="fas fa-user text-xl mb-1"></i>
                </div>
                <span class="text-xs font-medium">Account</span>
            </a>
        @else
            <a href="/login" class="flex flex-col items-center py-2 px-4 {{ request()->is('login*') || request()->is('register*') || request()->is('account*') ? 'text-black font-bold' : 'text-gray-400' }}">
                <div class="relative">
                    <i class="fas fa-user text-xl mb-1"></i>
                </div>
                <span class="text-xs font-medium">Account</span>
            </a>
        @endauth
    </div>
</div>

<!-- Updated CSS Styles -->
<style>
.mobile-bottom-nav {
    height: 70px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.mobile-bottom-nav a {
    transition: color 0.2s ease;
    min-width: 60px;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

/* Hover effect tetap menggunakan warna hitam */
.mobile-bottom-nav a:hover {
    color: #000000;
}

/* Add padding to body to prevent content being hidden behind bottom nav */
@media (max-width: 767px) {
    body {
        padding-bottom: 70px;
    }
}

/* Active state styling - WARNA HITAM BOLD */
.mobile-bottom-nav a.text-black i {
    transform: scale(1.1);
}

.mobile-bottom-nav a.font-bold {
    font-weight: 700;
}

/* Cart badge animation */
.mobile-bottom-nav .bg-red-500 {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}
</style>
@stack('scripts')
</body>
</html>