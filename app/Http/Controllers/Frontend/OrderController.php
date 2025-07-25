<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // HAPUS __construct() dengan middleware

    public function index()
    {
        $orders = collect(); // Empty for now
        return view('frontend.orders.index', compact('orders'));
    }

    public function show($orderNumber)
    {
        return redirect()->route('orders.index');
    }
}