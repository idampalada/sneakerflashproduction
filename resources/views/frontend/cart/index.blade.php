@extends('layouts.app')

@section('title', 'Shopping Cart - SneakerFlash')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>

    @if(isset($cartItems) && $cartItems->count() > 0)
        <div class="bg-white rounded-lg shadow-md p-6">
            {{-- Cart items will be here --}}
            <p>You have {{ $cartItems->count() }} items in your cart</p>
            
            <div class="mt-6 text-right">
                <p class="text-xl font-bold">Total: Rp {{ number_format($total ?? 0, 0, ',', '.') }}</p>
                <a href="/checkout" class="mt-4 inline-block bg-blue-600 text-white px-8 py-3 rounded-lg">
                    Proceed to Checkout
                </a>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-gray-500 text-xl mb-4">Your cart is empty</p>
            <a href="/products" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700">
                Start Shopping
            </a>
        </div>
    @endif
</div>
@endsection