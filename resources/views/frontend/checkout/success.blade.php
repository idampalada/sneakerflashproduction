{{-- File: resources/views/frontend/checkout/success.blade.php --}}
@extends('layouts.app')

@section('title', 'Order Success - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Success Message -->
        <div class="bg-white rounded-lg shadow-md p-8 text-center mb-8">
            <div class="mb-6">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-3xl text-green-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h1>
                <p class="text-gray-600">Thank you for your purchase. We'll process your order shortly.</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 gap-4 text-left">
                    <div>
                        <p class="text-sm text-gray-500">Order Number</p>
                        <p class="font-semibold text-lg">{{ $order->order_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Order Date</p>
                        <p class="font-semibold">{{ $order->created_at->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Customer</p>
                        <p class="font-semibold">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Amount</p>
                        <p class="font-semibold text-lg text-green-600">
                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <a href="/products" 
                   class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Continue Shopping
                </a>
                <br>
                <a href="/orders/{{ $order->order_number }}" 
                   class="inline-block text-blue-600 hover:text-blue-800">
                    View Order Details
                </a>
            </div>
        </div>

        <!-- Order Details -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Details</h2>
            
            <!-- Order Items -->
            <div class="space-y-4 mb-6">
                @if($order->orderItems && $order->orderItems->count() > 0)
                    @foreach($order->orderItems as $item)
                        <div class="flex items-center space-x-4 py-4 border-b border-gray-200 last:border-b-0">
                            <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900">{{ $item->product_name }}</h3>
                                <p class="text-sm text-gray-500">Quantity: {{ $item->quantity }}</p>
                                <p class="text-sm text-gray-500">
                                    Rp {{ number_format($item->product_price, 0, ',', '.') }} each
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">
                                    Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Order Summary -->
            <div class="border-t pt-4">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-semibold">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping:</span>
                        <span class="font-semibold">Rp {{ number_format($order->shipping_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax (PPN 11%):</span>
                        <span class="font-semibold">Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t pt-2">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span class="text-green-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="mt-6 pt-6 border-t">
                <h3 class="font-semibold text-gray-900 mb-3">Shipping Address</h3>
                <div class="text-gray-600">
                    @if($order->shipping_address)
                        <p>{{ $order->shipping_address['first_name'] }} {{ $order->shipping_address['last_name'] }}</p>
                        <p>{{ $order->shipping_address['address'] }}</p>
                        <p>{{ $order->shipping_address['postal_code'] }}</p>
                        <p>{{ $order->shipping_address['phone'] }}</p>
                    @endif
                </div>
            </div>

            <!-- Payment Information -->
            <div class="mt-6 pt-6 border-t">
                <h3 class="font-semibold text-gray-900 mb-3">Payment Information</h3>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-yellow-600 mr-3"></i>
                        <div>
                            <p class="font-medium text-yellow-800">Payment Pending</p>
                            <p class="text-sm text-yellow-700">
                                Please transfer the total amount to our bank account. 
                                We'll contact you shortly with payment instructions.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            @if($order->notes)
                <!-- Order Notes -->
                <div class="mt-6 pt-6 border-t">
                    <h3 class="font-semibold text-gray-900 mb-3">Order Notes</h3>
                    <p class="text-gray-600">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Contact Information -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-900 mb-3">Need Help?</h3>
            <p class="text-blue-700 mb-4">
                If you have any questions about your order, please contact us:
            </p>
            <div class="space-y-2 text-blue-700">
                <p><i class="fas fa-envelope mr-2"></i> Email: support@sneakerflash.com</p>
                <p><i class="fas fa-phone mr-2"></i> Phone: +62 21 1234 5678</p>
                <p><i class="fas fa-clock mr-2"></i> Business Hours: Mon-Fri 9AM-6PM</p>
            </div>
        </div>
    </div>
</div>
@endsection