{{-- File: resources/views/frontend/checkout/payment.blade.php --}}
@extends('layouts.app')

@section('title', 'Payment - SneakerFlash')

{{-- ADD: Meta tags untuk pass data ke JavaScript --}}
@section('head')
<meta name="midtrans-client-key" content="{{ config('services.midtrans.client_key') }}">
<meta name="midtrans-production" content="{{ config('services.midtrans.is_production') ? 'true' : 'false' }}">

@if($order->status === 'pending' && !empty($snapToken))
<meta name="snap-token" content="{{ $snapToken }}">
<meta name="order-number" content="{{ $order->order_number }}">
@endif
@endsection

@section('content')
@php
  $meta = $order->meta_data ? json_decode($order->meta_data, true) : [];
  $shippingMethod = $meta['shipping_method'] ?? $meta['shipping_method_detail'] ?? 'Shipping';
@endphp

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Complete Your Payment</h1>
            <p class="text-gray-600">Order #{{ $order->order_number }}</p>
        </div>

        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
            
            <div class="space-y-4">
                @foreach($order->orderItems as $item)
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-medium">{{ $item->product_name }}</h3>
                        <p class="text-sm text-gray-600">Qty: {{ $item->quantity }}</p>
                    </div>
                    <p class="font-medium">Rp {{ number_format($item->total_price, 0, ',', '.') }}</p>
                </div>
                @endforeach
                
                <hr>
                
                <div class="flex justify-between">
    <span>Subtotal</span>
    <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
</div>

<div class="flex justify-between">
    <span>Shipping ({{ $shippingMethod }})</span>
    <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
</div>

@if(($order->discount_amount ?? 0) > 0)
<div class="flex justify-between">
    <span>Discount</span>
    <span>- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
</div>
@endif

{{-- NO TAX: hanya tampilkan kalau > 0 --}}
@if(($order->tax_amount ?? 0) > 0)
<div class="flex justify-between">
    <span>Tax</span>
    <span>Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</span>
</div>
@endif

<hr>

<div class="flex justify-between text-lg font-bold">
    <span>Total</span>
    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
</div>


        <!-- Payment Actions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="text-center">
@if($order->status === 'pending' && !empty($snapToken))
                    <div id="payment-status" class="mb-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="font-medium text-blue-800 mb-2">Ready for Payment</h3>
                            <p class="text-blue-700">Click the button below to open the payment gateway</p>
                        </div>
                    </div>

                    <button id="pay-button" 
                            class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium text-lg">
                        Pay Now - Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                    </button>

                    <div class="mt-4 text-sm text-gray-600">
                        <p>Secure payment powered by Midtrans</p>
                        <p>Supports Credit Card, Bank Transfer, E-Wallet, and more</p>
                    </div>

@elseif($order->status === 'paid')
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <h3 class="font-medium text-green-800 mb-2">✅ Payment Completed</h3>
                        <p class="text-green-700">Your payment has been successfully processed</p>
                    </div>

                    <a href="{{ route('checkout.success', ['orderNumber' => $order->order_number]) }}" 
                       class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                        View Order Details
                    </a>

                @else
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <h3 class="font-medium text-red-800 mb-2">❌ Payment Session Expired</h3>
                        <p class="text-red-700">Please contact support or create a new order</p>
                    </div>

                    <a href="{{ route('home') }}" 
                       class="bg-gray-600 text-white px-8 py-3 rounded-lg hover:bg-gray-700 transition-colors font-medium">
                        Back to Home
                    </a>
                @endif

                <!-- Alternative Actions -->
                <div class="mt-6 pt-6 border-t">
                    <div class="flex justify-center space-x-4">
                        <a href="{{ route('checkout.success', ['orderNumber' => $order->order_number]) }}" 
                           class="text-gray-600 hover:text-gray-800 underline">
                            View Order Details
                        </a>
                        <span class="text-gray-400">|</span>
                        <a href="{{ route('home') }}" 
                           class="text-gray-600 hover:text-gray-800 underline">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($order->status === 'pending' && $snapToken)
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Payment page loaded');
    
    // FIXED: Ambil data dari meta tags (no more red lines!)
    const snapToken = document.querySelector('meta[name="snap-token"]')?.getAttribute('content');
    const orderNumber = document.querySelector('meta[name="order-number"]')?.getAttribute('content');
    
    console.log('💳 Snap token available:', snapToken ? 'Yes' : 'No');
    console.log('📋 Order number:', orderNumber);
    
    if (!snapToken || !orderNumber) {
        console.error('❌ Missing payment data');
        showPaymentError('Payment data not available');
        return;
    }
    
    // Load Midtrans script
    loadMidtransScript().then(() => {
        console.log('✅ Midtrans loaded successfully');
        
        // Setup pay button
        const payButton = document.getElementById('pay-button');
        if (payButton) {
            payButton.addEventListener('click', function() {
                console.log('💳 Pay button clicked');
                openMidtransPayment(snapToken, orderNumber);
            });
        }
        
    }).catch((error) => {
        console.error('❌ Failed to load Midtrans:', error);
        showPaymentError('Failed to load payment system. Please refresh the page.');
    });
});

function loadMidtransScript() {
    return new Promise((resolve, reject) => {
        if (window.snap) {
            resolve();
            return;
        }

        const clientKey = document.querySelector('meta[name="midtrans-client-key"]')?.getAttribute('content');
        const isProduction = document.querySelector('meta[name="midtrans-production"]')?.getAttribute('content') === 'true';

        if (!clientKey) {
            reject(new Error('Midtrans client key not found'));
            return;
        }

        const script = document.createElement('script');
        script.src = isProduction 
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
        script.setAttribute('data-client-key', clientKey);

        script.onload = () => {
            setTimeout(() => {
                if (window.snap) {
                    resolve();
                } else {
                    reject(new Error('Snap object not available'));
                }
            }, 500);
        };

        script.onerror = () => reject(new Error('Failed to load Midtrans script'));
        document.head.appendChild(script);
    });
}

function openMidtransPayment(snapToken, orderNumber) {
    console.log('🔐 Opening Midtrans payment...');
    
    if (!window.snap) {
        showPaymentError('Payment system not loaded. Please refresh the page.');
        return;
    }

    updatePaymentStatus('Opening payment gateway...', 'blue');

    try {
        window.snap.pay(snapToken, {
            onSuccess: function(result) {
                console.log('✅ Payment success:', result);
                updatePaymentStatus('Payment successful! Redirecting...', 'green');
                
                setTimeout(() => {
                    window.location.href = `/checkout/success/${orderNumber}?payment=success`;
                }, 2000);
            },

            onPending: function(result) {
                console.log('⏳ Payment pending:', result);
                updatePaymentStatus('Payment is being processed...', 'yellow');
                
                setTimeout(() => {
                    window.location.href = `/checkout/success/${orderNumber}?payment=pending`;
                }, 3000);
            },

            onError: function(result) {
                console.error('❌ Payment error:', result);
                showPaymentError('Payment failed. Please try again.');
            },

            onClose: function() {
                console.log('🔒 Payment closed by user');
                updatePaymentStatus('Payment was cancelled. You can retry anytime.', 'gray');
            }
        });
        
    } catch (error) {
        console.error('❌ Error opening payment:', error);
        showPaymentError('Failed to open payment gateway.');
    }
}

function updatePaymentStatus(message, color) {
    const statusDiv = document.getElementById('payment-status');
    if (!statusDiv) return;

    const colorClasses = {
        blue: 'bg-blue-50 border-blue-200 text-blue-800',
        green: 'bg-green-50 border-green-200 text-green-800',
        yellow: 'bg-yellow-50 border-yellow-200 text-yellow-800',
        red: 'bg-red-50 border-red-200 text-red-800',
        gray: 'bg-gray-50 border-gray-200 text-gray-800'
    };

    statusDiv.innerHTML = `
        <div class="${colorClasses[color]} border rounded-lg p-4">
            <p class="font-medium">${message}</p>
        </div>
    `;
}

function showPaymentError(message) {
    updatePaymentStatus('❌ ' + message, 'red');
}
</script>
@endif

@endsection