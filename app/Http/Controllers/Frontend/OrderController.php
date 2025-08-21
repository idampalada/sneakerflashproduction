<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display user's orders with auth check
     */
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view your orders.');
        }

        try {
            $user = Auth::user();
            
            $orders = Order::with(['orderItems.product'])
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhere('customer_email', $user->email);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            Log::info('Orders page accessed', [
                'user_id' => $user->id,
                'orders_count' => $orders->count(),
                'total_orders' => $orders->total()
            ]);

            return view('frontend.orders.index', compact('orders'));

        } catch (\Exception $e) {
            Log::error('Error loading orders page: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')->with('error', 'Failed to load orders. Please try again.');
        }
    }

    /**
     * Show specific order details with auth check
     */
    public function show($orderNumber)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to view order details.');
        }

        try {
            $user = Auth::user();
            
            $order = Order::with(['orderItems.product'])
                ->where('order_number', $orderNumber)
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhere('customer_email', $user->email);
                })
                ->first();

            if (!$order) {
                Log::warning('Order not found or access denied', [
                    'order_number' => $orderNumber,
                    'user_id' => $user->id,
                    'user_email' => $user->email
                ]);

                return redirect()->route('orders.index')->with('error', 'Order not found.');
            }

            Log::info('Order details accessed', [
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'order_status' => $order->status
            ]);

            return view('frontend.orders.show', compact('order'));

        } catch (\Exception $e) {
            Log::error('Error loading order details: ' . $e->getMessage(), [
                'order_number' => $orderNumber,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('orders.index')->with('error', 'Failed to load order details.');
        }
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, $orderNumber)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required.'
            ], 401);
        }

        try {
            $user = Auth::user();
            
            $order = Order::where('order_number', $orderNumber)
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhere('customer_email', $user->email);
                })
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Order not found.'
                ], 404);
            }

            // Only allow cancellation for pending orders
            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'error' => 'Order cannot be cancelled. Current status: ' . $order->status
                ], 400);
            }

            $order->update(['status' => 'cancelled']);

            Log::info('Order cancelled', [
                'order_number' => $orderNumber,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error cancelling order: ' . $e->getMessage(), [
                'order_number' => $orderNumber,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel order. Please try again.'
            ], 500);
        }
    }

    /**
     * Download invoice
     */
    public function invoice($orderNumber)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to download invoice.');
        }

        try {
            $user = Auth::user();
            
            $order = Order::with(['orderItems.product'])
                ->where('order_number', $orderNumber)
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhere('customer_email', $user->email);
                })
                ->first();

            if (!$order) {
                return redirect()->route('orders.index')->with('error', 'Order not found.');
            }

            // Only allow invoice download for paid orders
            if ($order->status !== 'paid') {
                return redirect()->route('orders.show', $orderNumber)
                               ->with('error', 'Invoice not available. Order must be paid first.');
            }

            // Return invoice view or PDF
            return view('frontend.orders.invoice', compact('order'));

        } catch (\Exception $e) {
            Log::error('Error generating invoice: ' . $e->getMessage(), [
                'order_number' => $orderNumber,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('orders.index')
                           ->with('error', 'Failed to generate invoice.');
        }
    }
}