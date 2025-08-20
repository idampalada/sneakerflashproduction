<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\OrderController;
use App\Http\Controllers\Frontend\ProfileController;
use App\Http\Controllers\Frontend\AddressController;
use App\Http\Controllers\Frontend\GineeSyncController;
use App\Http\Controllers\Frontend\GineeWebhookController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Middleware\VerifyCsrfToken;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// =====================================
// WEBHOOK ROUTES - TANPA CSRF PROTECTION
// =====================================
Route::withoutMiddleware(['web'])->group(function () {
    // PRIMARY WEBHOOK - Use this URL in Midtrans Dashboard
    Route::post('/checkout/payment-notification', [CheckoutController::class, 'paymentNotification'])
          ->name('checkout.payment-notification');

    // ALTERNATIVE WEBHOOK ENDPOINTS
    Route::post('/checkout/payment/notification', [CheckoutController::class, 'paymentNotification'])
          ->name('checkout.payment.notification');
    Route::post('/midtrans/notification', [CheckoutController::class, 'paymentNotification'])
          ->name('midtrans.notification');
    Route::post('/webhook/midtrans', [CheckoutController::class, 'paymentNotification'])
          ->name('webhook.midtrans');
    Route::post('/api/midtrans/webhook', [CheckoutController::class, 'paymentNotification'])
          ->name('api.midtrans.webhook');

    // TEST WEBHOOK ENDPOINTS (for debugging)
    Route::post('/api/payment/test-webhook', [CheckoutController::class, 'testWebhook'])
          ->name('api.payment.test-webhook');
    Route::post('/test-webhook-no-validation', [CheckoutController::class, 'testWebhookNoValidation'])
          ->name('test.webhook.no.validation');
    
    // SIMPLE TEST WEBHOOK
    Route::post('/test-webhook-simple', function(Request $request) {
        \Log::info('=== SIMPLE WEBHOOK TEST ===', [
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
            'raw_body' => $request->getContent(),
            'timestamp' => now()
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Simple webhook test received',
            'received_data' => $request->all(),
            'order_id' => $request->get('order_id'),
            'timestamp' => now()
        ]);
    })->name('test.webhook.simple');

    // DEBUG WEBHOOK
    Route::any('/debug-webhook', function(Request $request) {
        \Log::info('=== DEBUG WEBHOOK ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
            'input' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Debug webhook received',
            'method' => $request->method(),
            'timestamp' => now(),
            'data_received' => $request->all()
        ]);
    })->name('debug.webhook');
    
    // GET INFO ENDPOINT (untuk cek di browser)
    Route::get('/checkout/payment-notification', function() {
        return response()->json([
            'status' => 'info',
            'message' => 'Webhook endpoint is working',
            'note' => 'This endpoint only accepts POST requests from Midtrans',
            'methods_allowed' => ['POST'],
            'current_time' => now(),
            'environment' => app()->environment()
        ]);
    })->name('checkout.payment-notification.info');
});

// =====================================
// PRODUCT ROUTES - ⭐ ENHANCED WITH GROUPING
// =====================================
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/variant/{productId}/{size?}', [ProductController::class, 'getVariant'])->name('get-variant');
    Route::get('/{slug}', [ProductController::class, 'show'])->name('show');
});

// Product category routes
Route::get('/mens', [ProductController::class, 'mens'])->name('products.mens');
Route::get('/womens', [ProductController::class, 'womens'])->name('products.womens');
Route::get('/unisex', [ProductController::class, 'unisex'])->name('products.unisex');
Route::get('/brand', [ProductController::class, 'brand'])->name('products.brand');
Route::get('/accessories', [ProductController::class, 'accessories'])->name('products.accessories');
Route::get('/sale', [ProductController::class, 'sale'])->name('products.sale');
Route::get('/search', [ProductController::class, 'search'])->name('search');
Route::get('/filter', [ProductController::class, 'filter'])->name('products.filter');

// Categories
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// =====================================
// SHOPPING CART - ⭐ ENHANCED WITH SIZE SUPPORT & CART KEY
// =====================================
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    
    // Add product to cart - supports both URL parameter and POST data
    Route::post('/add/{productId?}', [CartController::class, 'add'])->name('add');
    
    // ⭐ ENHANCED: Update cart item - uses identifier (product ID or cart key)
    Route::patch('/update/{identifier}', [CartController::class, 'update'])->name('update');
    Route::patch('/{identifier}', [CartController::class, 'update'])->name('update.alternative');
    
    // ⭐ ENHANCED: Remove cart item - uses identifier (product ID or cart key)  
    Route::delete('/remove/{identifier}', [CartController::class, 'remove'])->name('remove');
    Route::delete('/{identifier}', [CartController::class, 'remove'])->name('remove.alternative');
    
    // Clear entire cart
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');
    Route::delete('/', [CartController::class, 'clear'])->name('clear.alternative');
    
    // API endpoints
    Route::get('/count', [CartController::class, 'getCartCount'])->name('count');
    Route::get('/data', [CartController::class, 'getCartData'])->name('data');
    Route::post('/sync', [CartController::class, 'syncCart'])->name('sync');
});

// =====================================
// AUTHENTICATION ROUTES
// =====================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');
    Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::post('/logout', [GoogleController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/password/reset', function() {
    return view('auth.passwords.email');
})->name('password.request');

// =====================================
// CHECKOUT & PAYMENT ROUTES
// =====================================
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'store'])->name('store');
    
    // RajaOngkir integration
    Route::get('/search-destinations', [CheckoutController::class, 'searchDestinations'])->name('search-destinations');
    Route::post('/shipping', [CheckoutController::class, 'calculateShipping'])->name('shipping');
    Route::post('/calculate-shipping', [CheckoutController::class, 'calculateShipping'])->name('calculate-shipping');
    
    // Payment flow
    Route::get('/payment/{orderNumber}', [CheckoutController::class, 'payment'])->name('payment');
    Route::get('/success/{orderNumber}', [CheckoutController::class, 'success'])->name('success');
    
    // Payment callbacks dari Midtrans (GET routes)
    Route::get('/payment-success', [CheckoutController::class, 'paymentSuccess'])->name('payment-success');
    Route::get('/payment-pending', [CheckoutController::class, 'paymentPending'])->name('payment-pending');
    Route::get('/payment-error', [CheckoutController::class, 'paymentError'])->name('payment-error');
    
    // Midtrans redirect callbacks
    Route::get('/finish', [CheckoutController::class, 'paymentFinish'])->name('finish');
    Route::get('/unfinish', [CheckoutController::class, 'paymentUnfinish'])->name('unfinish');
    Route::get('/error', [CheckoutController::class, 'paymentError'])->name('error');
});

// =====================================
// AUTHENTICATED USER ROUTES
// =====================================
Route::middleware(['auth'])->group(function () {
    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{orderNumber}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{orderNumber}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    
    // Wishlist
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/toggle/{productId}', [WishlistController::class, 'toggle'])->name('toggle');
        Route::delete('/remove/{productId}', [WishlistController::class, 'remove'])->name('remove');
        Route::delete('/clear', [WishlistController::class, 'clear'])->name('clear');
        Route::post('/move-to-cart/{productId}', [WishlistController::class, 'moveToCart'])->name('moveToCart');
        Route::get('/count', [WishlistController::class, 'getCount'])->name('count');
        Route::post('/check', [WishlistController::class, 'checkProducts'])->name('check');
    });
    
    // =====================================
    // PROFILE MANAGEMENT ROUTES - COMPLETE & FIXED
    // =====================================
    Route::prefix('profile')->name('profile.')->group(function () {
        // Main profile routes
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        
        // Password management
        Route::get('/password', [ProfileController::class, 'showChangePassword'])->name('password.change');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
        
        // API endpoints for profile data
        Route::get('/data', [ProfileController::class, 'getProfileData'])->name('data');

        // =====================================
        // ADDRESS MANAGEMENT ROUTES - COMPLETE
        // =====================================
        Route::prefix('addresses')->name('addresses.')->group(function () {
            // Web routes
            Route::get('/', [AddressController::class, 'index'])->name('index');
            Route::get('/create', [AddressController::class, 'create'])->name('create');
            Route::post('/store', [AddressController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AddressController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AddressController::class, 'update'])->name('update');
            Route::post('/{id}/set-primary', [AddressController::class, 'setPrimary'])->name('set-primary');
            Route::delete('/{id}', [AddressController::class, 'destroy'])->name('destroy');
            
            // API routes for AJAX/JSON requests
            Route::get('/{id}/show', [AddressController::class, 'show'])->name('show');
            Route::get('/api/all', [AddressController::class, 'getAddresses'])->name('api.all');
            Route::get('/api/primary', [AddressController::class, 'getPrimaryAddress'])->name('api.primary');
        });
    });
});

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
// API ROUTES
// =====================================
Route::prefix('api')->name('api.')->group(function() {
    // Products
    Route::get('/products/search', [ProductController::class, 'quickSearch'])->name('products.search');
    Route::get('/products/{id}/variants', [ProductController::class, 'getVariants'])->name('products.variants');
    Route::get('/products/{id}/stock', [ProductController::class, 'checkStock'])->name('products.stock');
    
    // Cart
    Route::get('/cart/count', [CartController::class, 'getCartCount'])->name('cart.count');
    Route::get('/cart/data', [CartController::class, 'getCartData'])->name('cart.data');
    
    // Checkout & RajaOngkir
    Route::get('/checkout/search-destinations', [CheckoutController::class, 'searchDestinations'])->name('checkout.search-destinations');
    Route::post('/checkout/shipping', [CheckoutController::class, 'calculateShipping'])->name('checkout.shipping');
    
    // Payment status
    Route::get('/payment/status/{orderNumber}', [CheckoutController::class, 'getPaymentStatus'])->name('payment.status');
    Route::post('/payment/retry/{orderNumber}', [OrderController::class, 'retryPayment'])->name('payment.retry');
    
    // Manual payment status check (for debugging)
    Route::get('/payment/check/{orderNumber}', [CheckoutController::class, 'checkPaymentStatus'])->name('payment.check');
    
    // Authenticated API routes
    Route::middleware('auth')->group(function() {
        // Wishlist
        Route::get('/wishlist/count', [WishlistController::class, 'getCount'])->name('wishlist.count');
        Route::post('/wishlist/toggle/{productId}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::post('/wishlist/check', [WishlistController::class, 'checkProducts'])->name('wishlist.check');
        
        // Profile API routes
        Route::get('/profile/data', [ProfileController::class, 'getProfileData'])->name('profile.data');
        
        // Address API routes
        Route::prefix('addresses')->name('addresses.')->group(function() {
            Route::get('/all', [AddressController::class, 'getAddresses'])->name('all');
            Route::get('/primary', [AddressController::class, 'getPrimaryAddress'])->name('primary');
            Route::get('/{id}', [AddressController::class, 'show'])->name('show');
            Route::post('/{id}/set-primary', [AddressController::class, 'setPrimary'])->name('set-primary');
            Route::delete('/{id}', [AddressController::class, 'destroy'])->name('destroy');
            Route::post('/points/validate', [CheckoutController::class, 'validatePoints'])->name('points.validate');
    Route::post('/points/remove', [CheckoutController::class, 'removePoints'])->name('points.remove');
    Route::get('/points/current', [CheckoutController::class, 'getCurrentPoints'])->name('points.current');
    
    // Get user points balance
    Route::get('/points/balance', function() {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        return response()->json([
            'success' => true,
            'balance' => $user->points_balance ?? 0,
            'formatted_balance' => number_format($user->points_balance ?? 0, 0, ',', '.')
        ]);
    })->name('points.balance');
});
        });
   
    
    // Newsletter
    Route::post('/newsletter', function() {
        return response()->json(['success' => true, 'message' => 'Subscribed successfully!']);
    })->name('newsletter');
});

// =====================================
// DEBUG ROUTES (Remove in production)
// =====================================
Route::get('/debug/cart', function() {
    $cart = Session::get('cart', []);
    $cartItems = collect();
    
    foreach ($cart as $cartKey => $details) {
        $productId = $details['product_id'] ?? null;
        $product = null;
        
        if ($productId) {
            $product = \App\Models\Product::find($productId);
        }
        
        $cartItems->push([
            'cart_key' => $cartKey,
            'raw_data' => $details,
            'product_exists' => $product ? true : false,
            'product_active' => $product ? $product->is_active : false,
            'current_stock' => $product ? $product->stock_quantity : 0,
            'size_info' => [
                'cart_size' => $details['size'] ?? 'not_set',
                'product_available_sizes' => $product ? $product->available_sizes : null,
                'size_type' => $details['size'] ? gettype($details['size']) : 'null'
            ]
        ]);
    }
    
    return response()->json([
        'session_cart_raw' => $cart,
        'processed_items' => $cartItems,
        'total_items' => count($cart),
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token()
    ], 200, [], JSON_PRETTY_PRINT);
})->name('debug.cart');

// Helper route to clear session for testing
Route::get('/debug/cart/clear', function() {
    Session::flush();
    return response()->json([
        'message' => 'Session cleared',
        'timestamp' => now()
    ]);
})->name('debug.cart.clear');

// =====================================
// REDIRECTS & FALLBACKS
// =====================================
Route::get('/shop', function() {
    return redirect()->route('products.index');
});

Route::get('/category/{slug}', function($slug) {
    return redirect()->route('categories.show', $slug);
});

Route::get('/product/{slug}', function($slug) {
    return redirect()->route('products.show', $slug);
});

Route::fallback(function() {
    abort(404);
});
// Add these routes to your web.php file in the cart section

// Existing cart routes...
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::post('/add/{productId?}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/update/{identifier}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/remove/{identifier}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/clear', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/sync', [CartController::class, 'syncCart'])->name('cart.sync');
    
    // ADDED: Get cart count for AJAX
    Route::get('/count', [CartController::class, 'getCartCount'])->name('cart.count');
    
    // ADDED: Get cart data for checkout Order Summary
    Route::get('/data', [CartController::class, 'getCartData'])->name('cart.data');
});
// =====================================
// API ROUTES
// =====================================
Route::prefix('api')->name('api.')->group(function() {
    // Products
    Route::get('/products/search', [ProductController::class, 'quickSearch'])->name('products.search');
    Route::get('/products/{id}/variants', [ProductController::class, 'getVariants'])->name('products.variants');
    Route::get('/products/{id}/stock', [ProductController::class, 'checkStock'])->name('products.stock');
    
    // Cart
    Route::get('/cart/count', [CartController::class, 'getCartCount'])->name('cart.count');
    Route::get('/cart/data', [CartController::class, 'getCartData'])->name('cart.data');
    
    // Coupons/Vouchers - NEW
Route::prefix('vouchers')->name('vouchers.')->group(function() {
    Route::post('/apply', [\App\Http\Controllers\Frontend\VoucherController::class, 'apply'])->name('apply');
    Route::post('/remove', [\App\Http\Controllers\Frontend\VoucherController::class, 'remove'])->name('remove');
    Route::get('/current', [\App\Http\Controllers\Frontend\VoucherController::class, 'current'])->name('current');
    Route::post('/validate', [\App\Http\Controllers\Frontend\VoucherController::class, 'validate'])->name('validate');
    Route::get('/available', [\App\Http\Controllers\Frontend\VoucherController::class, 'available'])->name('available');
});
    
    // Checkout & RajaOngkir
    Route::get('/checkout/search-destinations', [CheckoutController::class, 'searchDestinations'])->name('checkout.search-destinations');
    Route::post('/checkout/shipping', [CheckoutController::class, 'calculateShipping'])->name('checkout.shipping');
    
    // Payment status
    Route::get('/payment/status/{orderNumber}', [CheckoutController::class, 'getPaymentStatus'])->name('payment.status');
    Route::post('/payment/retry/{orderNumber}', [OrderController::class, 'retryPayment'])->name('payment.retry');
    
    // Manual payment status check (for debugging)
    Route::get('/payment/check/{orderNumber}', [CheckoutController::class, 'checkPaymentStatus'])->name('payment.check');
    });

    // Existing profile routes (keep your current ones)
Route::middleware(['auth'])->group(function () {
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    
    // API routes for profile data (existing)
    Route::get('/api/profile/data', [ProfileController::class, 'getProfileData'])->name('api.profile.data');
    
    // New API route for zodiac data
    Route::get('/api/zodiac/{zodiac}', [ProfileController::class, 'getZodiacData'])->name('api.zodiac.data');
    
});

Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])
    ->name('checkout.success');

// Payment Callback Routes (untuk Midtrans)
Route::post('/payment/callback/success', [CheckoutController::class, 'paymentSuccess'])
    ->name('payment.callback.success');

Route::post('/payment/callback/pending', [CheckoutController::class, 'paymentPending'])
    ->name('payment.callback.pending');

Route::post('/payment/callback/error', [CheckoutController::class, 'paymentError'])
    ->name('payment.callback.error');

// Alternative routes untuk GET requests (jika diperlukan)
Route::get('/payment/success', [CheckoutController::class, 'paymentSuccess'])
    ->name('payment.success');

Route::get('/payment/pending', [CheckoutController::class, 'paymentPending'])
    ->name('payment.pending');

Route::get('/payment/error', [CheckoutController::class, 'paymentError'])
    ->name('payment.error');

    Route::middleware(['auth'])->group(function () {
    
    // Points management routes
    Route::post('/api/points/validate', [CheckoutController::class, 'validatePoints']);
    Route::post('/api/points/apply', [CheckoutController::class, 'validatePoints']);
    Route::post('/api/points/remove', [CheckoutController::class, 'removePoints']);
    Route::get('/api/points/current', [CheckoutController::class, 'getCurrentPoints']);
    
});

Route::middleware(['auth'])->prefix('integrations/ginee')->name('ginee.')->group(function () {
    // Stock Synchronization Routes
    Route::post('/pull-products', [GineeStockSyncController::class, 'pullProducts'])->name('pull.products');
    Route::post('/push-stock', [GineeStockSyncController::class, 'pushStock'])->name('push.stock');
    Route::get('/ginee-stock', [GineeStockSyncController::class, 'getGineeStock'])->name('ginee.stock');
    Route::get('/test-endpoints', [GineeStockSyncController::class, 'testEndpoints'])->name('test.endpoints');
});

Route::withoutMiddleware(['web'])
    ->prefix('api/webhooks/ginee')
    ->group(function () {
        // health & event-specific (sudah ada)
        Route::get('/health', fn () => response()->json(['ok'=>true,'ts'=>now()]));
        Route::post('/orders', [\App\Http\Controllers\Frontend\GineeWebhookController::class, 'orders'])
            ->name('webhooks.ginee.orders');
        Route::post('/master-products', [\App\Http\Controllers\Frontend\GineeWebhookController::class, 'masterProducts'])
            ->name('webhooks.ginee.master_products');

        // ➜ GLOBAL endpoint (baru)
        Route::match(['GET','POST'], '/global', [\App\Http\Controllers\Frontend\GineeWebhookController::class, 'global'])
            ->name('webhooks.ginee.global');
    });

        // Checkout routes
Route::post('/checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping']);