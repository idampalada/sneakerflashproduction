<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\OrderController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// =====================================
// PUBLIC ROUTES
// =====================================

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Products
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Categories
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// Shopping Cart (accessible for all users)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

// AJAX Cart Counter
Route::get('/api/cart/count', [CartController::class, 'getCartCount'])->name('cart.count');

// =====================================
// AUTHENTICATION ROUTES
// =====================================

// Guest routes (redirect to home if already authenticated)
Route::middleware('guest')->group(function () {
    // Login routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    
    // Register routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');
    
    // Google OAuth routes
    Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Logout route (requires authentication)
Route::post('/logout', [GoogleController::class, 'logout'])->name('logout')->middleware('auth');

// Password reset routes (optional - akan ditambahkan nanti)
Route::get('/password/reset', function() {
    return view('auth.passwords.email');
})->name('password.request');

// =====================================
// CHECKOUT ROUTES (Guest & Authenticated)
// =====================================

// Main checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// AJAX routes for checkout
Route::get('/checkout/cities', [CheckoutController::class, 'getCities'])->name('checkout.cities');
Route::post('/checkout/shipping', [CheckoutController::class, 'calculateShipping'])->name('checkout.shipping');

// Checkout completion routes
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/finish/{orderNumber}', [CheckoutController::class, 'finish'])->name('checkout.finish');
Route::get('/checkout/unfinish', [CheckoutController::class, 'unfinish'])->name('checkout.unfinish');
Route::get('/checkout/error', [CheckoutController::class, 'error'])->name('checkout.error');

// Payment notification (for payment gateways like Midtrans)
Route::post('/checkout/payment/notification', [CheckoutController::class, 'paymentNotification'])->name('checkout.notification');

// =====================================
// AUTHENTICATED USER ROUTES
// =====================================

Route::middleware(['auth'])->group(function () {
    // User Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
    
    // User Profile
    Route::get('/profile', function() {
        return view('frontend.profile.index');
    })->name('profile.index');
    
    Route::get('/profile/edit', function() {
        return view('frontend.profile.edit');
    })->name('profile.edit');
    
    // User Account Settings
    Route::patch('/profile', function() {
        // Profile update logic here
        return redirect()->route('profile.index')->with('success', 'Profile updated successfully');
    })->name('profile.update');
    
    // User Wishlist (if implemented)
    Route::get('/wishlist', function() {
        return view('frontend.wishlist.index');
    })->name('wishlist.index');
});

// =====================================
// SEARCH & FILTER ROUTES
// =====================================

// Advanced search
Route::get('/search', [ProductController::class, 'search'])->name('search');

// Product filters
Route::get('/filter', [ProductController::class, 'filter'])->name('products.filter');

// Brand pages
Route::get('/brands/{brand}', [ProductController::class, 'byBrand'])->name('products.brand');

// =====================================
// STATIC PAGES
// =====================================

Route::get('/about', function() {
    return view('frontend.pages.about');
})->name('about');

Route::get('/contact', function() {
    return view('frontend.pages.contact');
})->name('contact');

Route::post('/contact', function() {
    // Contact form submission logic
    return back()->with('success', 'Message sent successfully!');
})->name('contact.submit');

Route::get('/shipping-info', function() {
    return view('frontend.pages.shipping');
})->name('shipping.info');

Route::get('/returns', function() {
    return view('frontend.pages.returns');
})->name('returns');

Route::get('/size-guide', function() {
    return view('frontend.pages.size-guide');
})->name('size.guide');

Route::get('/terms', function() {
    return view('frontend.pages.terms');
})->name('terms');

Route::get('/privacy', function() {
    return view('frontend.pages.privacy');
})->name('privacy');

// =====================================
// API ROUTES (for AJAX calls)
// =====================================

Route::prefix('api')->group(function() {
    // Quick product search for autocomplete
    Route::get('/products/search', [ProductController::class, 'quickSearch'])->name('api.products.search');
    
    // Get product variants (size, color)
    Route::get('/products/{id}/variants', [ProductController::class, 'getVariants'])->name('api.products.variants');
    
    // Check product stock
    Route::get('/products/{id}/stock', [ProductController::class, 'checkStock'])->name('api.products.stock');
    
    // Newsletter subscription
    Route::post('/newsletter', function() {
        // Newsletter subscription logic
        return response()->json(['success' => true, 'message' => 'Subscribed successfully!']);
    })->name('api.newsletter');
});

// =====================================
// DEBUG ROUTES (Remove in production)
// =====================================

Route::prefix('debug')->group(function() {
    Route::get('/routes', function() {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ];
        });
        
        return response()->json($routes);
    });

    Route::get('/checkout', function() {
        dd([
            'session_cart' => session('cart', []),
            'cart_count' => count(session('cart', [])),
            'csrf_token' => csrf_token(),
            'user' => Auth::user(),
            'routes' => [
                'checkout.index' => route('checkout.index'),
                'checkout.store' => route('checkout.store'),
                'checkout.cities' => route('checkout.cities'),
                'checkout.shipping' => route('checkout.shipping'),
            ]
        ]);
    });

    Route::get('/session', function() {
        return response()->json([
            'cart' => session('cart', []),
            'user' => Auth::user(),
            'csrf' => csrf_token(),
            'all_session' => session()->all()
        ]);
    });

    Route::get('/categories', function() {
        $allCategories = \App\Models\Category::all();
        $activeCategories = \App\Models\Category::where('is_active', true)->get();
        
        return response()->json([
            'total_categories' => $allCategories->count(),
            'active_categories' => $activeCategories->count(),
            'categories' => $activeCategories->map(function($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'is_active' => $cat->is_active,
                    'products_count' => $cat->products()->count()
                ];
            })
        ]);
    });

    Route::get('/products', function() {
        $products = \App\Models\Product::with('category')->get();
        
        return response()->json([
            'total_products' => $products->count(),
            'active_products' => $products->where('is_active', true)->count(),
            'products' => $products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'stock' => $product->stock_quantity,
                    'category' => $product->category->name ?? 'No Category',
                    'is_active' => $product->is_active
                ];
            })
        ]);
    });

    Route::get('/clear-cart', function() {
        session()->forget('cart');
        return response()->json(['message' => 'Cart cleared', 'cart' => session('cart', [])]);
    });
});

// =====================================
// FALLBACK ROUTES
// =====================================

// Handle old URLs or redirects
Route::get('/shop', function() {
    return redirect()->route('products.index');
});

Route::get('/category/{slug}', function($slug) {
    return redirect()->route('categories.show', $slug);
});

Route::get('/product/{slug}', function($slug) {
    return redirect()->route('products.show', $slug);
});

// 404 handling for specific paths
Route::fallback(function() {
    abort(404);
});