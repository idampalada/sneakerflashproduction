<?php
// Replace: app/Http/Controllers/Frontend/HomeController.php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Get featured products from database
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category')
            ->take(8)
            ->get();

        // If no featured products, get any active products
        if ($featuredProducts->isEmpty()) {
            $featuredProducts = Product::query()
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->with('category')
                ->take(8)
                ->get();
        }

        // Get latest products from database
        $latestProducts = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category')
            ->latest('created_at')
            ->take(12)
            ->get();

        // Get active categories from database
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        return view('frontend.home', compact('featuredProducts', 'latestProducts', 'categories'));
    }
}