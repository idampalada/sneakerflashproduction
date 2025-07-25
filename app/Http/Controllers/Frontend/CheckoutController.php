<?php
// Update: app/Http/Controllers/Frontend/CheckoutController.php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    // HAPUS __construct() dengan middleware

    public function index()
    {
        // For now, redirect to cart if empty
        return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
    }

    public function store(Request $request)
    {
        return redirect()->route('home')->with('success', 'Order placed successfully!');
    }
}