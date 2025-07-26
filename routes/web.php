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

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/cities', [CheckoutController::class, 'getCities'])->name('checkout.getCities');
Route::post('/checkout/shipping', [CheckoutController::class, 'calculateShipping'])->name('checkout.calculateShipping');

// Payment callbacks (public endpoints)
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'finish'])->name('checkout.finish');
Route::get('/checkout/unfinish', [CheckoutController::class, 'unfinish'])->name('checkout.unfinish');
Route::get('/checkout/error', [CheckoutController::class, 'error'])->name('checkout.error');

// =====================================
// AUTHENTICATED USER ROUTES
// =====================================

Route::middleware(['auth'])->group(function () {
    // User Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
    
    // User Profile (akan ditambahkan nanti)
    Route::get('/profile', function() {
        return view('frontend.profile.index');
    })->name('profile.index');
    
    Route::get('/profile/edit', function() {
        return view('frontend.profile.edit');
    })->name('profile.edit');
});

// =====================================
// DEBUG ROUTES (Remove in production)
// =====================================

Route::get('/debug/categories', function() {
    $allCategories = \App\Models\Category::all();
    $activeCategories = \App\Models\Category::where('is_active', true)->get();
    
    echo "<h2>Debug Data Kategori</h2>";
    echo "<h3>Total Kategori: " . $allCategories->count() . "</h3>";
    echo "<h3>Kategori Aktif: " . $activeCategories->count() . "</h3>";
    
    if ($activeCategories->count() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f5f5f5;'><th style='padding: 8px; border: 1px solid #ddd;'>ID</th><th style='padding: 8px; border: 1px solid #ddd;'>Name</th><th style='padding: 8px; border: 1px solid #ddd;'>Slug</th><th style='padding: 8px; border: 1px solid #ddd;'>Active</th></tr>";
        foreach ($activeCategories as $cat) {
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$cat->id}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$cat->name}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$cat->slug}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>".($cat->is_active ? 'YES' : 'NO')."</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<br><a href='/' style='color: blue; text-decoration: underline;'>Back to Home</a>";
});

Route::get('/debug/add-sample-data', function() {
    echo "<h2>Adding Sample Data...</h2>";
    
    // Add sample categories if none exist
    if (\App\Models\Category::count() == 0) {
        $categories = [
            ['name' => 'Running Shoes', 'slug' => 'running-shoes', 'description' => 'Professional running shoes'],
            ['name' => 'Basketball Shoes', 'slug' => 'basketball-shoes', 'description' => 'High-performance basketball shoes'],
            ['name' => 'Casual Shoes', 'slug' => 'casual-shoes', 'description' => 'Comfortable everyday shoes'],
            ['name' => 'Training Shoes', 'slug' => 'training-shoes', 'description' => 'Versatile training shoes'],
        ];
        
        foreach ($categories as $index => $catData) {
            \App\Models\Category::create([
                'name' => $catData['name'],
                'slug' => $catData['slug'],
                'description' => $catData['description'],
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
            ]);
        }
        echo "<p style='color: green;'>✓ Sample categories added!</p>";
    } else {
        echo "<p style='color: orange;'>Categories already exist.</p>";
    }
    
    // Add sample products if none exist
    if (\App\Models\Product::count() == 0) {
        $categories = \App\Models\Category::all();
        if ($categories->count() > 0) {
            $products = [
                [
                    'name' => 'Nike Air Max 270',
                    'slug' => 'nike-air-max-270',
                    'short_description' => 'Comfortable running shoes',
                    'description' => 'Premium running shoes with air cushioning technology for maximum comfort.',
                    'price' => 2500000,
                    'category_id' => $categories->first()->id,
                    'brand' => 'Nike',
                    'is_active' => true,
                    'is_featured' => true,
                    'published_at' => now(),
                    'stock_quantity' => 50,
                ],
                [
                    'name' => 'Adidas Ultraboost 22',
                    'slug' => 'adidas-ultraboost-22',
                    'short_description' => 'Premium running shoes',
                    'description' => 'Advanced running shoes with boost technology for superior energy return.',
                    'price' => 3000000,
                    'sale_price' => 2400000,
                    'category_id' => $categories->first()->id,
                    'brand' => 'Adidas',
                    'is_active' => true,
                    'is_featured' => true,
                    'published_at' => now(),
                    'stock_quantity' => 30,
                ],
                [
                    'name' => 'Puma RS-X',
                    'slug' => 'puma-rs-x',
                    'short_description' => 'Retro-inspired sneakers',
                    'description' => 'Bold and chunky sneakers with retro-futuristic design elements.',
                    'price' => 1800000,
                    'category_id' => $categories->count() > 2 ? $categories[2]->id : $categories->first()->id,
                    'brand' => 'Puma',
                    'is_active' => true,
                    'is_featured' => false,
                    'published_at' => now(),
                    'stock_quantity' => 25,
                ],
            ];
            
            foreach ($products as $prodData) {
                \App\Models\Product::create($prodData);
            }
            echo "<p style='color: green;'>✓ Sample products added!</p>";
        } else {
            echo "<p style='color: red;'>Cannot add products: No categories found.</p>";
        }
    } else {
        echo "<p style='color: orange;'>Products already exist.</p>";
    }
    
    echo "<br><br>";
    echo "<a href='/debug/categories' style='color: blue; text-decoration: underline; margin-right: 20px;'>View Categories</a>";
    echo "<a href='/' style='color: blue; text-decoration: underline;'>Back to Home</a>";
});