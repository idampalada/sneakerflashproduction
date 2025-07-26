@extends('layouts.app')

@section('title', 'Checkout - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>

    <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Shipping Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Customer Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Customer Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" name="first_name" id="first_name" required
                                   value="{{ old('first_name') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            @error('first_name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" name="last_name" id="last_name" required
                                   value="{{ old('last_name') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            @error('last_name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" id="email" required
                                   value="{{ old('email') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            @error('email')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="tel" name="phone" id="phone" required
                                   value="{{ old('phone') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            @error('phone')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Shipping Address</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                            <textarea name="address" id="address" required rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="province_id" class="block text-sm font-medium text-gray-700 mb-2">Province *</label>
                                <select name="province_id" id="province_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                        onchange="loadCities()">
                                    <option value="">Select Province</option>
                                    @foreach($provinces as $province)
                                        @php
                                            $provinceId = $province['province_id'];
                                            $provinceName = $province['province'];
                                            $isSelected = old('province_id') == $provinceId;
                                        @endphp
                                        <option value="{{ $provinceId }}" @if($isSelected) selected @endif>
                                            {{ $provinceName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('province_id')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                                <select name="city_id" id="city_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                                        onchange="calculateShipping()">
                                    <option value="">Select City</option>
                                </select>
                                @error('city_id')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Postal Code *</label>
                            <input type="text" name="postal_code" id="postal_code" required
                                   value="{{ old('postal_code') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            @error('postal_code')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Shipping Method -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Shipping Method</h2>
                    
                    <div id="shipping-options" class="space-y-3">
                        <p class="text-gray-500">Please select your city to see shipping options</p>
                    </div>
                    
                    <input type="hidden" name="shipping_method" id="shipping_method">
                    <input type="hidden" name="shipping_cost" id="shipping_cost" value="0">
                </div>

                <!-- Order Notes -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Notes (Optional)</h2>
                    <textarea name="notes" id="notes" rows="3" placeholder="Any special instructions for your order?"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
                    
                    <!-- Cart Items -->
                    <div class="space-y-3 mb-6 max-h-60 overflow-y-auto">
                        @foreach($cartItems as $item)
                            @php
                                $itemId = $item['id'];
                                $itemName = $item['name'];
                                $itemQuantity = $item['quantity'];
                                $itemSubtotal = $item['subtotal'];
                                $itemImage = $item['image'] ?? null;
                            @endphp
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    @if($itemImage)
                                        <img src="{{ Storage::url($itemImage) }}" 
                                             alt="{{ $itemName }}"
                                             class="w-12 h-12 object-cover rounded">
                                    @else
                                        <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400 text-sm"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $itemName }}</p>
                                    <p class="text-sm text-gray-500">Qty: {{ $itemQuantity }}</p>
                                </div>
                                <div class="text-sm font-semibold text-gray-900">
                                    Rp {{ number_format($itemSubtotal, 0, ',', '.') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Order Totals -->
                    @php
                        $subtotalValue = $subtotal;
                        $taxValue = $subtotal * 0.11;
                        $totalValue = $subtotal + $taxValue;
                    @endphp
                    <div class="space-y-3 border-t pt-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-semibold" id="subtotal-display">Rp {{ number_format($subtotalValue, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping:</span>
                            <span class="font-semibold" id="shipping-display">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax (PPN 11%):</span>
                            <span class="font-semibold" id="tax-display">Rp {{ number_format($taxValue, 0, ',', '.') }}</span>
                        </div>
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span id="total-display">Rp {{ number_format($totalValue, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" id="place-order-btn" disabled
                            class="w-full mt-6 bg-gray-400 text-white py-3 rounded-lg font-medium cursor-not-allowed">
                        Place Order
                    </button>

                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Secure payment processing
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const subtotal = { $subtotal };
const taxRate = 0.11;

function loadCities() {
    const provinceId = document.getElementById('province_id').value;
    const citySelect = document.getElementById('city_id');
    
    if (!provinceId) {
        citySelect.innerHTML = '<option value="">Select City</option>';
        return;
    }
    
    citySelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch('/checkout/cities?province_id=' + provinceId)
        .then(response => response.json())
        .then(cities => {
            citySelect.innerHTML = '<option value="">Select City</option>';
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.city_id;
                option.textContent = city.city_name;
                citySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading cities:', error);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
        });
}

function calculateShipping() {
    const cityId = document.getElementById('city_id').value;
    const shippingOptions = document.getElementById('shipping-options');
    
    if (!cityId) {
        shippingOptions.innerHTML = '<p class="text-gray-500">Please select your city to see shipping options</p>';
        return;
    }
    
    shippingOptions.innerHTML = '<p class="text-gray-500">Calculating shipping options...</p>';
    
    const totalWeight = 1; // Default weight
    
    fetch('/checkout/shipping', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            destination_city: cityId,
            weight: totalWeight
        })
    })
    .then(response => response.json())
    .then(options => {
        if (options.length === 0) {
            shippingOptions.innerHTML = '<p class="text-red-500">No shipping options available for this location</p>';
            return;
        }
        
        let html = '';
        options.forEach((option, index) => {
            html += `
                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="shipping_option" value="${option.courier}_${option.service}" 
                           data-cost="${option.cost}" data-description="${option.courier} - ${option.service}"
                           onchange="selectShipping(this)" ${index === 0 ? 'checked' : ''}
                           class="mr-3">
                    <div class="flex-1">
                        <div class="font-medium">${option.courier} - ${option.service}</div>
                        <div class="text-sm text-gray-600">${option.description}</div>
                        <div class="text-sm text-gray-600">Estimated delivery: ${option.formatted_etd}</div>
                    </div>
                    <div class="font-semibold text-blue-600">${option.formatted_cost}</div>
                </label>
            `;
        });
        
        shippingOptions.innerHTML = html;
        
        // Auto-select first option
        if (options.length > 0) {
            document.getElementById('shipping_method').value = options[0].courier + ' - ' + options[0].service;
            document.getElementById('shipping_cost').value = options[0].cost;
            updateTotals(options[0].cost);
            enablePlaceOrderButton();
        }
    })
    .catch(error => {
        console.error('Error calculating shipping:', error);
        shippingOptions.innerHTML = '<p class="text-red-500">Error calculating shipping. Please try again.</p>';
    });
}

function selectShipping(radio) {
    document.getElementById('shipping_method').value = radio.dataset.description;
    document.getElementById('shipping_cost').value = radio.dataset.cost;
    updateTotals(parseInt(radio.dataset.cost));
    enablePlaceOrderButton();
}

function updateTotals(shippingCost) {
    const tax = subtotal * taxRate;
    const total = subtotal + shippingCost + tax;
    
    document.getElementById('shipping-display').textContent = 'Rp ' + shippingCost.toLocaleString('id-ID');
    document.getElementById('tax-display').textContent = 'Rp ' + Math.round(tax).toLocaleString('id-ID');
    document.getElementById('total-display').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}

function enablePlaceOrderButton() {
    const btn = document.getElementById('place-order-btn');
    const shippingMethod = document.getElementById('shipping_method').value;
    
    if (shippingMethod) {
        btn.disabled = false;
        btn.className = 'w-full mt-6 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium';
        btn.textContent = 'Place Order';
    }
}
</script>
@endpush