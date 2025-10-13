{{-- File: resources/views/frontend/orders/track-guest-result.blade.php --}}
@extends('layouts.app')

@section('title', 'Order #' . $order->order_number . ' Tracking - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-3xl font-bold text-gray-900">Order Tracking</h1>
                <a href="{{ route('track.guest-form') }}" 
                   class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Track Another Order
                </a>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="text-green-800 font-medium">Order found! Here are the latest details:</p>
                </div>
            </div>
        </div>

        <!-- Order Status Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">
                        Order #{{ $order->order_number }}
                    </h2>
                    
                    <div class="flex flex-wrap gap-2 mb-3">
                        <!-- Payment Status -->
                        @if($order->payment_status === 'pending')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                ⏳ Payment Pending
                            </span>
                        @elseif($order->payment_status === 'paid')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                ✅ Paid
                            </span>
                        @elseif($order->payment_status === 'failed')
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                                ❌ Payment Failed
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        @endif
                        
                        <!-- Order Status -->
                        @if($order->status === 'pending')
                            <span class="