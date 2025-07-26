<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        // For now, just return empty orders
        $orders = collect();
        
        // If user is authenticated, get their orders
        if (auth()->check()) {
            $orders = Order::where('user_id', auth()->id())
                          ->orderBy('created_at', 'desc')
                          ->paginate(10);
        }
        
        return view('frontend.orders.index', compact('orders'));
    }

    public function show($orderNumber)
    {
        // Try to find order by order number
        $order = Order::where('order_number', $orderNumber)->first();
        
        if (!$order) {
            return redirect()->route('orders.index')->with('error', 'Order not found.');
        }
        
        // If user is authenticated, check if order belongs to them
        if (auth()->check() && $order->user_id !== auth()->id()) {
            return redirect()->route('orders.index')->with('error', 'You can only view your own orders.');
        }
        
        // Load order items
        $order->load('orderItems');
        
        return view('frontend.orders.show', compact('order'));
    }
}