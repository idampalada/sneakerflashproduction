@extends('layouts.app')

@section('title', 'My Orders - SneakerFlash')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">My Orders</h1>

    @if(isset($orders) && $orders->count() > 0)
        <div class="space-y-6">
            @foreach($orders as $order)
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold">Order #{{ $order->order_number ?? 'N/A' }}</h3>
                            <p class="text-gray-600">{{ $order->created_at ?? 'N/A' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                                {{ $order->status ?? 'Processing' }}
                            </span>
                            <p class="mt-2 font-bold">
                                Rp {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-gray-500 text-xl mb-4">No orders found</p>
            <a href="/products" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700">
                Start Shopping
            </a>
        </div>
    @endif
</div>
@endsection