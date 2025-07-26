@extends('layouts.app')

@section('title', 'My Orders - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">My Orders</h1>

    @if(!auth()->check())
        <!-- Not logged in -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <div class="mb-4">
                <i class="fas fa-sign-in-alt text-4xl text-yellow-600"></i>
            </div>
            <h2 class="text-xl font-semibold text-yellow-800 mb-2">Please Log In</h2>
            <p class="text-yellow-700 mb-4">You need to be logged in to view your orders.</p>
            <a href="/login" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Log In
            </a>
        </div>
    @elseif(isset($orders) && $orders->count() > 0)
        <!-- Orders List -->
        <div class="space-y-6">
            @foreach($orders as $order)
                @php
                    $orderNumber = $order->order_number;
                    $orderDate = $order->created_at->format('d M Y, H:i');
                    $orderStatus = $order->status;
                    $orderTotal = $order->total_amount;
                    $paymentStatus = $order->payment_status;
                @endphp
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div class="mb-4 lg:mb-0">
                            <div class="flex items-center space-x-4 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">Order #{{ $orderNumber }}</h3>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($orderStatus === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($orderStatus === 'processing') bg-blue-100 text-blue-800
                                    @elseif($orderStatus === 'shipped') bg-purple-100 text-purple-800
                                    @elseif($orderStatus === 'delivered') bg-green-100 text-green-800
                                    @elseif($orderStatus === 'cancelled') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($orderStatus) }}
                                </span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($paymentStatus === 'pending') bg-orange-100 text-orange-800
                                    @elseif($paymentStatus === 'paid') bg-green-100 text-green-800
                                    @elseif($paymentStatus === 'failed') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    Payment: {{ ucfirst($paymentStatus) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">Ordered on {{ $orderDate }}</p>
                            <p class="text-lg font-semibold text-gray-900 mt-1">
                                Total: Rp {{ number_format($orderTotal, 0, ',', '.') }}
                            </p>
                        </div>
                        
                        <div class="flex space-x-3">
                            <a href="/orders/{{ $orderNumber }}" 
                               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                                View Details
                            </a>
                            @if($orderStatus === 'delivered')
                                <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                    Review Order
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    @if($order->orderItems && $order->orderItems->count() > 0)
                        <!-- Order Items Preview -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex flex-wrap gap-4">
                                @foreach($order->orderItems->take(3) as $item)
                                    @php
                                        $itemName = $item->product_name;
                                        $itemQuantity = $item->quantity;
                                    @endphp
                                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                                        <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
                                            <i class="fas fa-box text-xs"></i>
                                        </div>
                                        <span>{{ $itemName }} (x{{ $itemQuantity }})</span>
                                    </div>
                                @endforeach
                                @if($order->orderItems->count() > 3)
                                    <span class="text-sm text-gray-500">
                                        +{{ $order->orderItems->count() - 3 }} more items
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if(method_exists($orders, 'hasPages') && $orders->hasPages())
            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @endif
    @else
        <!-- No Orders -->
        <div class="text-center py-16">
            <div class="mb-6">
                <i class="fas fa-shopping-bag text-6xl text-gray-300"></i>
            </div>
            <h2 class="text-2xl font-semibold text-gray-600 mb-4">No Orders Yet</h2>
            <p class="text-gray-500 mb-8">You haven't placed any orders yet. Start shopping to see your orders here!</p>
            <a href="/products" 
               class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                Start Shopping
            </a>
        </div>
    @endif
</div>
@endsection