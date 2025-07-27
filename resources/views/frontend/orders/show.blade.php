{{-- File: resources/views/frontend/orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Order Details - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                <a href="/" class="hover:text-blue-600">Home</a>
                <span>/</span>
                <a href="/orders" class="hover:text-blue-600">My Orders</a>
                <span>/</span>
                <span class="text-gray-900">{{ $order->order_number }}</span>
            </nav>
            
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Order Details</h1>
                    <p class="text-gray-600 mt-1">Order {{ $order->order_number }}</p>
                </div>
                
                <!-- Order Status Badge -->
                <div class="mt-4 md:mt-0">
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'processing' => 'bg-blue-100 text-blue-800', 
                            'shipped' => 'bg-purple-100 text-purple-800',
                            'delivered' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800'
                        ];
                        $statusColor = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColor }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Order Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Order Status Timeline -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Status</h2>
                    
                    <div class="relative">
                        <!-- Timeline -->
                        <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        
                        <!-- Steps -->
                        <div class="relative space-y-8">
                            <!-- Order Placed -->
                            <div class="flex items-center">
                                <div class="relative flex-shrink-0">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-green-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900">Order Placed</h3>
                                    <p class="text-sm text-gray-500">{{ $order->created_at->format('d M Y, H:i') }}</p>
                                </div>
                            </div>

                            <!-- Payment Confirmed -->
                            <div class="flex items-center">
                                <div class="relative flex-shrink-0">
                                    @if($order->payment_status === 'paid')
                                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-credit-card text-green-600"></i>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-credit-card text-gray-400"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900">Payment {{ $order->payment_status === 'paid' ? 'Confirmed' : 'Pending' }}</h3>
                                    <p class="text-sm text-gray-500">
                                        @if($order->payment_status === 'paid')
                                            Payment received and confirmed
                                        @else
                                            Waiting for payment confirmation
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Processing -->
                            <div class="flex items-center">
                                <div class="relative flex-shrink-0">
                                    @if(in_array($order->status, ['processing', 'shipped', 'delivered']))
                                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-cog text-blue-600"></i>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-cog text-gray-400"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900">Processing</h3>
                                    <p class="text-sm text-gray-500">
                                        @if(in_array($order->status, ['processing', 'shipped', 'delivered']))
                                            Order is being prepared for shipping
                                        @else
                                            Waiting for payment to start processing
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Shipped -->
                            <div class="flex items-center">
                                <div class="relative flex-shrink-0">
                                    @if(in_array($order->status, ['shipped', 'delivered']))
                                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-truck text-purple-600"></i>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-truck text-gray-400"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900">Shipped</h3>
                                    <p class="text-sm text-gray-500">
                                        @if($order->shipped_at)
                                            Shipped on {{ $order->shipped_at->format('d M Y, H:i') }}
                                        @elseif(in_array($order->status, ['shipped', 'delivered']))
                                            Order has been shipped
                                        @else
                                            Will be shipped after processing
                                        @endif
                                    </p>
                                    
                                    <!-- Tracking Number Display -->
                                    @if($order->tracking_number)
                                        <div class="mt-2 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-purple-900">Tracking Number</p>
                                                    <p class="text-lg font-mono font-bold text-purple-800">{{ $order->tracking_number }}</p>
                                                </div>
                                                <button onclick="copyTrackingNumber()" 
                                                        class="px-3 py-1 bg-purple-600 text-white text-sm rounded hover:bg-purple-700 transition-colors">
                                                    <i class="fas fa-copy mr-1"></i>Copy
                                                </button>
                                            </div>
                                            <p class="text-xs text-purple-700 mt-2">
                                                Use this tracking number to monitor your package delivery status
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Delivered -->
                            <div class="flex items-center">
                                <div class="relative flex-shrink-0">
                                    @if($order->status === 'delivered')
                                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-home text-green-600"></i>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-home text-gray-400"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-900">Delivered</h3>
                                    <p class="text-sm text-gray-500">
                                        @if($order->delivered_at)
                                            Delivered on {{ $order->delivered_at->format('d M Y, H:i') }}
                                        @elseif($order->status === 'delivered')
                                            Order has been delivered
                                        @else
                                            Will be delivered after shipping
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Order Items</h2>
                    
                    <div class="space-y-4">
                        @if($order->orderItems && $order->orderItems->count() > 0)
                            @foreach($order->orderItems as $item)
                                <div class="flex items-center space-x-4 py-4 border-b border-gray-200 last:border-b-0">
                                    <!-- Product Image Placeholder -->
                                    <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                    
                                    <!-- Product Details -->
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900 text-lg">{{ $item->product_name }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">SKU: {{ $item->product_sku ?? 'N/A' }}</p>
                                        <div class="flex items-center mt-2 text-sm text-gray-600">
                                            <span class="mr-4">
                                                <i class="fas fa-box mr-1"></i>Quantity: {{ $item->quantity }}
                                            </span>
                                            <span>
                                                <i class="fas fa-tag mr-1"></i>
                                                Rp {{ number_format($item->product_price, 0, ',', '.') }} each
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Item Total -->
                                    <div class="text-right">
                                        <p class="font-semibold text-lg text-gray-900">
                                            Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-box-open text-4xl mb-4"></i>
                                <p>No items found for this order</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column - Order Summary & Details -->
            <div class="space-y-6">
                <!-- Order Summary -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping:</span>
                            <span class="font-medium">Rp {{ number_format($order->shipping_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax (PPN 11%):</span>
                            <span class="font-medium">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                        </div>
                        @if($order->discount_amount > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Discount:</span>
                                <span class="font-medium">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span class="text-green-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Information -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Information</h2>
                    
                    <div class="space-y-4 text-sm">
                        <div>
                            <span class="text-gray-600 block">Order Number:</span>
                            <span class="font-mono font-medium">{{ $order->order_number }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 block">Order Date:</span>
                            <span class="font-medium">{{ $order->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 block">Payment Method:</span>
                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 block">Payment Status:</span>
                            @php
                                $paymentColors = [
                                    'pending' => 'text-yellow-600',
                                    'paid' => 'text-green-600',
                                    'failed' => 'text-red-600',
                                    'cancelled' => 'text-red-600'
                                ];
                                $paymentColor = $paymentColors[$order->payment_status] ?? 'text-gray-600';
                            @endphp
                            <span class="font-medium {{ $paymentColor }}">{{ ucfirst($order->payment_status) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Shipping Address</h2>
                    
                    <div class="text-sm text-gray-600">
                        @if($order->shipping_address)
                            <p class="font-medium text-gray-900">{{ $order->customer_name }}</p>
                            <p class="mt-1">{{ $order->shipping_address['address'] ?? '' }}</p>
                            <p>{{ $order->shipping_address['postal_code'] ?? '' }}</p>
                            @if(isset($order->shipping_address['phone']))
                                <p class="mt-2">
                                    <i class="fas fa-phone mr-1"></i>{{ $order->shipping_address['phone'] }}
                                </p>
                            @endif
                        @else
                            <p class="text-gray-500">No shipping address available</p>
                        @endif
                    </div>
                </div>

                <!-- Customer Support -->
                <div class="bg-blue-50 rounded-lg border border-blue-200 p-6">
                    <h2 class="text-lg font-semibold text-blue-900 mb-2">Need Help?</h2>
                    <p class="text-sm text-blue-700 mb-4">
                        If you have any questions about your order, feel free to contact our customer support.
                    </p>
                    <div class="space-y-2 text-sm">
                        <a href="mailto:support@sneakerflash.com" class="flex items-center text-blue-600 hover:text-blue-800">
                            <i class="fas fa-envelope mr-2"></i>
                            support@sneakerflash.com
                        </a>
                        <a href="tel:+62123456789" class="flex items-center text-blue-600 hover:text-blue-800">
                            <i class="fas fa-phone mr-2"></i>
                            +62 123 456 789
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 flex flex-col sm:flex-row gap-4">
            <a href="/orders" 
               class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-center font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back to Orders
            </a>
            
            @if($order->status === 'delivered')
                <button class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    <i class="fas fa-star mr-2"></i>Write Review
                </button>
            @endif
            
            @if(in_array($order->status, ['pending', 'processing']) && $order->payment_status === 'pending')
                <button class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                    <i class="fas fa-times mr-2"></i>Cancel Order
                </button>
            @endif
        </div>
    </div>
</div>

<!-- Copy Tracking Number Script -->
<script>
function copyTrackingNumber() {
    const trackingNumber = '{{ $order->tracking_number }}';
    navigator.clipboard.writeText(trackingNumber).then(function() {
        // Show success message
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
        button.classList.add('bg-green-600');
        button.classList.remove('bg-purple-600');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-600');
            button.classList.add('bg-purple-600');
        }, 2000);
    });
}
</script>
@endsection