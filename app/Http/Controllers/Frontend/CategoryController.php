<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product; // TAMBAHKAN INI - INI YANG MENYEBABKAN ERROR
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show($slug, Request $request)
    {
        $category = Category::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $query = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('category_id', $category->id)
            ->with('category');

        // Filter by brand
        if ($request->brand) {
            $query->where('brand', $request->brand);
        }

        // Filter by price range
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search within category
        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('brand', 'like', $search);
            });
        }

        // Sort
        $sortBy = $request->sort ?? 'latest';
        switch ($sortBy) {
            case 'price_low':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default:
                $query->latest('created_at');
        }

        $products = $query->paginate(12)->withQueryString();

        // Get brands in this category
        $brands = Product::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('category_id', $category->id)
            ->whereNotNull('brand')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        return view('frontend.categories.show', compact('category', 'products', 'brands'));
    }
}