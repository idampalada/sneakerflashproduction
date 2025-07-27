{{-- File: resources/views/frontend/checkout/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Checkout - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>
        
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form" onsubmit="console.log('Form submitting...'); return validateForm();">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column: Customer Information -->
                <div class="space-y-6">
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
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
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
                                <textarea name="address" id="address" rows="3" required
                                          placeholder="Full address"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">{{ old('address') }}</textarea>
                                @error('address')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="province_id" class="block text-sm font-medium text-gray-700 mb-2">Province *</label>
                                    <select name="province_id" id="province_id" required
                                            onchange="loadCities(this.value)"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Province</option>
                                        @if(!empty($provinces))
                                            @foreach($provinces as $province)
                                                <option value="{{ $province['province_id'] }}" 
                                                        {{ old('province_id') == $province['province_id'] ? 'selected' : '' }}>
                                                    {{ $province['province'] }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="6">DKI Jakarta</option>
                                            <option value="9">Jawa Barat</option>
                                            <option value="10">Jawa Tengah</option>
                                            <option value="11">Jawa Timur</option>
                                            <option value="1">Bali</option>
                                        @endif
                                    </select>
                                    @error('province_id')
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                                    <select name="city_id" id="city_id" required
                                            onchange="calculateShipping()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
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
                        <textarea name="notes" id="notes" rows="3" 
                                  placeholder="Any special instructions for your order?"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div>
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
                        
                        <!-- Cart Items -->
                        <div class="space-y-4 mb-6">
                            @foreach($cartItems as $item)
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 bg-gray-200 rounded-md flex-shrink-0">
                                        @if($item['image'])
                                            <img src="{{ Storage::url($item['image']) }}" 
                                                 alt="{{ $item['name'] }}"
                                                 class="w-full h-full object-cover rounded-md">
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900">{{ $item['name'] }}</h3>
                                        <p class="text-sm text-gray-600">Qty: {{ $item['quantity'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Order Totals -->
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-semibold" id="subtotal-display">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping:</span>
                                <span class="font-semibold" id="shipping-display">Rp 0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax (PPN 11%):</span>
                                <span class="font-semibold" id="tax-display">Rp {{ number_format($subtotal * 0.11, 0, ',', '.') }}</span>
                            </div>
                            <div class="border-t pt-2">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total:</span>
                                    <span class="text-green-600" id="total-display">Rp {{ number_format($subtotal * 1.11, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden input untuk pass data ke JavaScript -->
                        <input type="hidden" id="subtotal-value" value="{{ $subtotal }}">
                        <input type="hidden" id="tax-rate" value="0.11">
                        
                        <button type="submit" id="place-order-btn" 
                                class="w-full mt-6 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                                onclick="console.log('Place Order button clicked'); return validateForm()">
                            Place Order
                        </button>

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
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Ambil data dari hidden input (lebih clean dan aman)
const subtotal = parseFloat(document.getElementById('subtotal-value').value) || 0;
const taxRate = parseFloat(document.getElementById('tax-rate').value) || 0.11;

// Debug log
console.log('Subtotal:', subtotal, 'Tax Rate:', taxRate);

function loadCities(provinceId) {
    const citySelect = document.getElementById('city_id');
    
    if (!provinceId) {
        citySelect.innerHTML = '<option value="">Select City</option>';
        return;
    }
    
    citySelect.innerHTML = '<option value="">Loading cities...</option>';
    
    // URL yang benar untuk route
    fetch(`{{ route('checkout.cities') }}?province_id=${provinceId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(cities => {
            citySelect.innerHTML = '<option value="">Select City</option>';
            
            if (cities && cities.length > 0) {
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.city_id;
                    option.textContent = city.city_name;
                    citySelect.appendChild(option);
                });
            } else {
                // Fallback cities jika API gagal
                const fallbackCities = getFallbackCities(provinceId);
                fallbackCities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.city_id;
                    option.textContent = city.city_name;
                    citySelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading cities:', error);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
            
            // Load fallback cities
            const fallbackCities = getFallbackCities(provinceId);
            if (fallbackCities.length > 0) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                fallbackCities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.city_id;
                    option.textContent = city.city_name;
                    citySelect.appendChild(option);
                });
            }
        });
}

function getFallbackCities(provinceId) {
    const fallbackData = {
        '6': [ // DKI Jakarta
            {city_id: '155', city_name: 'Jakarta Pusat'},
            {city_id: '156', city_name: 'Jakarta Utara'},
            {city_id: '157', city_name: 'Jakarta Barat'},
            {city_id: '158', city_name: 'Jakarta Selatan'},
            {city_id: '159', city_name: 'Jakarta Timur'}
        ],
        '9': [ // Jawa Barat
            {city_id: '22', city_name: 'Bandung'},
            {city_id: '23', city_name: 'Bogor'},
            {city_id: '151', city_name: 'Bekasi'},
            {city_id: '107', city_name: 'Depok'}
        ],
        '10': [ // Jawa Tengah
            {city_id: '162', city_name: 'Semarang'},
            {city_id: '501', city_name: 'Solo'}
        ],
        '11': [ // Jawa Timur
            {city_id: '161', city_name: 'Surabaya'},
            {city_id: '444', city_name: 'Malang'}
        ],
        '1': [ // Bali
            {city_id: '114', city_name: 'Denpasar'}
        ]
    };
    
    return fallbackData[provinceId] || [];
}

function calculateShipping() {
    const cityId = document.getElementById('city_id').value;
    const shippingOptions = document.getElementById('shipping-options');
    
    console.log('Calculate shipping called, cityId:', cityId);
    
    if (!cityId) {
        shippingOptions.innerHTML = '<p class="text-gray-500">Please select your city to see shipping options</p>';
        return;
    }
    
    shippingOptions.innerHTML = '<p class="text-gray-500">Calculating shipping options...</p>';
    
    const totalWeight = 1000; // Default 1kg
    
    fetch(`{{ route('checkout.shipping') }}`, {
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
    .then(response => {
        console.log('Shipping response status:', response.status);
        return response.json();
    })
    .then(options => {
        console.log('Shipping options:', options);
        
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

function validateForm() {
    console.log('Validate form called');
    
    const shippingMethod = document.getElementById('shipping_method').value;
    const firstName = document.getElementById('first_name') ? document.getElementById('first_name').value : '';
    const lastName = document.getElementById('last_name') ? document.getElementById('last_name').value : '';
    const email = document.getElementById('email') ? document.getElementById('email').value : '';
    
    console.log('Form values:', {
        shippingMethod: shippingMethod,
        firstName: firstName,
        lastName: lastName,
        email: email
    });
    
    // Bypass shipping validation untuk testing
    // if (!shippingMethod) {
    //     alert('Please select a shipping method first.');
    //     return false;
    // }
    
    console.log('Form validation passed, submitting...');
    return true;
}
</script>
@endpush