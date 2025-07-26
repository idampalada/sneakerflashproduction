@extends('layouts.app')

@section('title', 'Shopping Cart - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>

    @if(isset($cartItems) && $cartItems->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    @foreach($cartItems as $item)
                        @php
                            $itemId = $item['id'];
                            $itemName = $item['name'];
                            $itemPrice = $item['price'];
                            $itemQuantity = $item['quantity'];
                            $itemSubtotal = $item['subtotal'];
                            $itemImage = $item['image'] ?? null;
                        @endphp
                        <div class="p-6 border-b border-gray-200 last:border-b-0">
                            <div class="flex items-center space-x-4">
                                <!-- Product Image -->
                                <div class="flex-shrink-0">
                                    @if($itemImage)
                                        <img src="{{ Storage::url($itemImage) }}" 
                                             alt="{{ $itemName }}"
                                             class="w-20 h-20 object-cover rounded-lg">
                                    @else
                                        <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    @endif
                                </div>

                                <!-- Product Info -->
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ $itemName }}</h3>
                                    <p class="text-gray-600">
                                        Rp {{ number_format($itemPrice, 0, ',', '.') }}
                                    </p>
                                </div>

                                <!-- Quantity Controls -->
                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center">
                                        <button type="button" 
                                                onclick="decreaseQuantity({ $itemId })"
                                                class="w-8 h-8 rounded-l bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <input type="number" 
                                               id="quantity-{{ $itemId }}"
                                               value="{{ $itemQuantity }}" 
                                               min="1"
                                               readonly
                                               class="w-16 h-8 text-center border-t border-b border-gray-200 focus:outline-none">
                                        <button type="button" 
                                                onclick="increaseQuantity({ $itemId })"
                                                class="w-8 h-8 rounded-r bg-gray-200 hover:bg-gray-300 flex items-center justify-center">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Subtotal -->
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">
                                        Rp {{ number_format($itemSubtotal, 0, ',', '.') }}
                                    </p>
                                </div>

                                <!-- Remove Button -->
                                <div>
                                    <form action="{{ route('cart.remove', $itemId) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-800 p-2"
                                                onclick="return confirm('Remove this item from cart?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Cart Actions -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                    <a href="{{ route('products.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                    </a>
                    
                    <form action="{{ route('cart.clear') }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="text-red-600 hover:text-red-800 font-medium"
                                onclick="return confirm('Clear all items from cart?')">
                            <i class="fas fa-trash mr-2"></i>Clear Cart
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-semibold">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Shipping:</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Tax:</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <a href="{{ route('checkout.index') }}" 
                       class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium text-center block">
                        <i class="fas fa-lock mr-2"></i>Proceed to Checkout
                    </a>

                    <!-- Security Badge -->
                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Secure checkout with 256-bit SSL encryption
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Empty Cart -->
        <div class="text-center py-16">
            <div class="mb-6">
                <i class="fas fa-shopping-cart text-6xl text-gray-300"></i>
            </div>
            <h2 class="text-2xl font-semibold text-gray-600 mb-4">Your cart is empty</h2>
            <p class="text-gray-500 mb-8">Looks like you haven't added any items to your cart yet.</p>
            <a href="{{ route('products.index') }}" 
               class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                Start Shopping
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function updateCart(productId) {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/cart/' + productId;
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
    
    // Add method
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'PATCH';
    form.appendChild(methodInput);
    
    // Add quantity
    const quantityInput = document.createElement('input');
    quantityInput.type = 'hidden';
    quantityInput.name = 'quantity';
    quantityInput.value = document.getElementById('quantity-' + productId).value;
    form.appendChild(quantityInput);
    
    // Submit form
    document.body.appendChild(form);
    form.submit();
}

function increaseQuantity(productId) {
    const input = document.getElementById('quantity-' + productId);
    if (input) {
        input.value = parseInt(input.value) + 1;
        updateCart(productId);
    }
}

function decreaseQuantity(productId) {
    const input = document.getElementById('quantity-' + productId);
    if (input && parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateCart(productId);
    }
}
</script>
@endpush