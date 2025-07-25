<?php
// Pastikan file ini ada: app/Http/Controllers/Frontend/CartController.php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        // For now, return simple cart view with dummy data
        $cartItems = collect(); // Empty collection for testing
        $total = 0;

        return view('frontend.cart.index', compact('cartItems', 'total'));
    }

    public function add(Request $request)
    {
        // Simple response for testing
        return back()->with('success', 'Product added to cart successfully!');
    }

    public function update(Request $request, $id)
    {
        // Simple response for testing
        return back()->with('success', 'Cart updated successfully!');
    }

    public function remove($id)
    {
        // Simple response for testing
        return back()->with('success', 'Item removed from cart!');
    }

    public function clear()
    {
        // Simple response for testing
        return redirect()->route('cart.index')->with('success', 'Cart cleared successfully!');
    }
}