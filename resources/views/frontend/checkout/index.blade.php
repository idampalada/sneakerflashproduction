{{-- File: resources/views/frontend/checkout/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Checkout - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>
        
        <!-- Step Indicator -->
        <div class="flex justify-between mb-8 px-8">
            <div class="step active" id="step-1">
                <div class="step-number">1</div>
                <div class="step-title">Personal Information</div>
            </div>
            <div class="step" id="step-2">
                <div class="step-number">2</div>
                <div class="step-title">Addresses</div>
            </div>
            <div class="step" id="step-3">
                <div class="step-number">3</div>
                <div class="step-title">Shipping Method</div>
            </div>
            <div class="step" id="step-4">
                <div class="step-number">4</div>
                <div class="step-title">Payment</div>
            </div>
        </div>
        
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

        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Checkout Steps -->
                <div class="lg:col-span-2">
                    
                    <!-- Step 1: Personal Information -->
                    <div class="checkout-section active" id="section-1">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Personal Information</h2>
                            
                            @if(!Auth::check())
                                <!-- Login Option -->
                                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                                    <p class="text-sm text-gray-700 mb-3">Already have an account? 
                                        <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Log in instead!</a>
                                    </p>
                                    
                                    <p class="text-sm text-gray-600 mb-3">Or connect with social account:</p>
                                    <a href="{{ route('auth.google') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                        Google
                                    </a>
                                </div>
                            @endif
                            
                            <!-- Social Title -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Social title</label>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="social_title" value="Mr." class="mr-2">
                                        <span class="text-sm">Mr.</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="social_title" value="Mrs." class="mr-2">
                                        <span class="text-sm">Mrs.</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Name Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First name *</label>
                                    <input type="text" name="first_name" id="first_name" required
                                           value="{{ old('first_name') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Only letters and the dot (.) character, followed by a space, are allowed.</p>
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last name *</label>
                                    <input type="text" name="last_name" id="last_name" required
                                           value="{{ old('last_name') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Only letters and the dot (.) character, followed by a space, are allowed.</p>
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="email" id="email" required
                                       value="{{ old('email') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            @if(!Auth::check())
                                <!-- Create Account Option -->
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="create_account" id="create_account" value="1" 
                                               onchange="togglePassword()" class="mr-3">
                                        <span class="text-sm font-medium">Create an account (optional)</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">And save time on your next order!</p>
                                </div>
                                
                                <!-- Password Fields -->
                                <div id="password-fields" class="hidden mb-4">
                                    <div class="mb-4">
                                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                                        <input type="password" name="password" id="password" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                        <p class="text-xs text-gray-500 mt-1">Enter a password between 8 and 72 characters</p>
                                    </div>
                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Phone -->
                            <div class="mb-4">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                <input type="tel" name="phone" id="phone" required
                                       value="{{ old('phone') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <!-- Birthdate -->
                            <div class="mb-4">
                                <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="text-gray-400">Optional</span> Birthdate
                                </label>
                                <input type="date" name="birthdate" id="birthdate"
                                       value="{{ old('birthdate') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <!-- Newsletter -->
                            <div class="mb-4">
                                <label class="flex items-start">
                                    <input type="checkbox" name="newsletter_subscribe" value="1" class="mr-3 mt-1">
                                    <div>
                                        <span class="text-sm font-medium">Sign up for our newsletter</span>
                                        <p class="text-xs text-gray-500 italic">*Subscribing to our newsletter, get exclusive offers, early discounts, and other interesting programs*</p>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Privacy Policy -->
                            <div class="mb-6">
                                <label class="flex items-start">
                                    <input type="checkbox" name="privacy_accepted" id="privacy_accepted" required class="mr-3 mt-1">
                                    <div>
                                        <span class="text-sm font-medium">Customer data privacy *</span>
                                        <p class="text-xs text-gray-500 italic">*The personal data you provide is used to answer queries, process orders or allow access to specific information. You have the right to modify and delete all the personal information found in the "My Account" page.*</p>
                                    </div>
                                </label>
                            </div>
                            
                            <button type="button" onclick="nextStep(2)" 
                                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                Continue
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Addresses -->
                    <div class="checkout-section hidden" id="section-2">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Addresses</h2>
                            <p class="text-sm text-gray-600 mb-6">The selected address will be used both as your personal address (for invoice) and as your delivery address.</p>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                                    <textarea name="address" id="address" rows="3" required
                                              placeholder="Full address"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">{{ old('address') }}</textarea>
                                </div>
                                
                                <div>
                                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Zip/Postal Code *</label>
                                    <input type="text" name="postal_code" id="postal_code" required
                                           value="{{ old('postal_code') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                                    <select name="country" id="country" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                        <option value="Indonesia" selected>Indonesia</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="province_id" class="block text-sm font-medium text-gray-700 mb-2">State *</label>
                                    <select name="province_id" id="province_id" required
                                            onchange="loadCities(this.value)"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                        <option value="">Please choose</option>
                                        @if(!empty($provinces))
                                            @foreach($provinces as $province)
                                                <option value="{{ $province['province_id'] }}">
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
                                </div>
                                
                                <div>
                                    <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                                    <select name="city_id" id="city_id" required
                                            onchange="calculateShipping()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="flex space-x-4 mt-8">
                                <button type="button" onclick="prevStep(1)" 
                                        class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                                    Previous
                                </button>
                                <button type="button" onclick="nextStep(3)" 
                                        class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                    Continue
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Shipping Method -->
                    <div class="checkout-section hidden" id="section-3">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Shipping Method</h2>
                            
                            <div id="shipping-options" class="space-y-3 min-h-[200px]">
                                <p class="text-gray-500">Please select your city to see shipping options</p>
                            </div>
                            
                            <input type="hidden" name="shipping_method" id="shipping_method">
                            <input type="hidden" name="shipping_cost" id="shipping_cost" value="0">
                            
                            <div class="flex space-x-4 mt-8">
                                <button type="button" onclick="prevStep(2)" 
                                        class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                                    Previous
                                </button>
                                <button type="button" onclick="nextStep(4)" id="continue-step-3"
                                        class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                    Continue
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Payment -->
                    <div class="checkout-section hidden" id="section-4">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Payment</h2>
                            
                            <div class="space-y-3 mb-6">
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="bank_transfer" checked class="mr-4">
                                    <div class="flex-1">
                                        <div class="font-medium">Bank Transfer</div>
                                        <div class="text-sm text-gray-600">Transfer via ATM, Internet Banking, or Mobile Banking</div>
                                    </div>
                                </label>
                                
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="credit_card" class="mr-4">
                                    <div class="flex-1">
                                        <div class="font-medium">Credit Card</div>
                                        <div class="text-sm text-gray-600">Visa, Mastercard, JCB</div>
                                    </div>
                                </label>
                                
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="ewallet" class="mr-4">
                                    <div class="flex-1">
                                        <div class="font-medium">E-Wallet</div>
                                        <div class="text-sm text-gray-600">GoPay, OVO, DANA, ShopeePay</div>
                                    </div>
                                </label>
                                
                                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="cod" class="mr-4">
                                    <div class="flex-1">
                                        <div class="font-medium">Cash on Delivery (COD)</div>
                                        <div class="text-sm text-gray-600">Pay when the order is delivered</div>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Order Notes -->
                            <div class="mb-6">
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Order Notes (Optional)</label>
                                <textarea name="notes" id="notes" rows="3" 
                                          placeholder="Any special instructions for your order?"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                            </div>
                            
                            <div class="flex space-x-4 mt-8">
                                <button type="button" onclick="prevStep(3)" 
                                        class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                                    Previous
                                </button>
                                <button type="submit" id="place-order-btn" 
                                        class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
                        
                        <div class="mb-4">
                            <p class="text-sm text-gray-600">{{ count($cartItems) }} Item{{ count($cartItems) > 1 ? 's' : '' }}</p>
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
                            <div class="border-t pt-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total (tax incl.):</span>
                                    <span class="text-2xl font-bold" id="total-display">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm text-gray-500">Included taxes:</span>
                                    <span class="text-sm font-medium" id="tax-display">Rp 0</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden inputs -->
                        <input type="hidden" id="subtotal-value" value="{{ $subtotal }}">
                        <input type="hidden" id="tax-rate" value="0.11">
                        
                        <!-- Security Badge -->
                        <div class="mt-6 text-center">
                            <p class="text-xs text-gray-500">
                                🔒 Secure checkout with 256-bit SSL encryption
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.step {
    flex: 1;
    text-align: center;
    position: relative;
}

.step.active .step-number {
    background-color: #3b82f6;
    color: white;
}

.step.completed .step-number {
    background-color: #10b981;
    color: white;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: 600;
    border: 3px solid #e5e7eb;
}

.step-title {
    font-size: 0.875rem;
    color: #6b7280;
}

.step.active .step-title {
    color: #3b82f6;
    font-weight: 600;
}

.checkout-section.active {
    display: block !important;
}
</style>

<script>
let currentStep = 1;
const subtotal = parseFloat(document.getElementById('subtotal-value').value) || 0;
const taxRate = parseFloat(document.getElementById('tax-rate').value) || 0.11;

function nextStep(step) {
    if (validateCurrentStep()) {
        showStep(step);
    }
}

function prevStep(step) {
    showStep(step);
}

function showStep(step) {
    // Hide all sections
    document.querySelectorAll('.checkout-section').forEach(section => {
        section.classList.remove('active');
        section.classList.add('hidden');
    });
    
    // Reset all step indicators
    document.querySelectorAll('.step').forEach(stepEl => {
        stepEl.classList.remove('active', 'completed');
    });
    
    // Mark completed steps
    for (let i = 1; i < step; i++) {
        document.getElementById(`step-${i}`).classList.add('completed');
    }
    
    // Show current step
    document.getElementById(`section-${step}`).classList.remove('hidden');
    document.getElementById(`section-${step}`).classList.add('active');
    document.getElementById(`step-${step}`).classList.add('active');
    
    currentStep = step;
}

function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const privacyAccepted = document.getElementById('privacy_accepted').checked;
            
            if (!firstName || !lastName || !email || !phone || !privacyAccepted) {
                alert('Please fill in all required fields and accept the privacy policy.');
                return false;
            }
            break;
            
        case 2:
            const address = document.getElementById('address').value.trim();
            const provinceId = document.getElementById('province_id').value;
            const cityId = document.getElementById('city_id').value;
            const postalCode = document.getElementById('postal_code').value.trim();
            
            if (!address || !provinceId || !cityId || !postalCode) {
                alert('Please fill in all required address fields.');
                return false;
            }
            break;
            
        case 3:
            const shippingMethod = document.getElementById('shipping_method').value;
            if (!shippingMethod) {
                alert('Please select a shipping method.');
                return false;
            }
            break;
    }
    return true;
}

function togglePassword() {
    const checkbox = document.getElementById('create_account');
    const passwordFields = document.getElementById('password-fields');
    
    if (checkbox.checked) {
        passwordFields.classList.remove('hidden');
    } else {
        passwordFields.classList.add('hidden');
    }
}

function loadCities(provinceId) {
    const citySelect = document.getElementById('city_id');
    
    if (!provinceId) {
        citySelect.innerHTML = '<option value="">Select City</option>';
        return;
    }
    
    citySelect.innerHTML = '<option value="">Loading cities...</option>';
    
    fetch(`/checkout/cities?province_id=${provinceId}`)
        .then(response => response.json())
        .then(cities => {
            citySelect.innerHTML = '<option value="">Select City</option>';
            
            if (cities && cities.length > 0) {
                cities.forEach(city => {
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
    
    fetch(`/checkout/shipping`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            destination_city: cityId,
            weight: 1000
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
}

function updateTotals(shippingCost) {
    const tax = subtotal * taxRate;
    const total = subtotal + shippingCost + tax;
    
    document.getElementById('shipping-display').textContent = 'Rp ' + shippingCost.toLocaleString('id-ID');
    document.getElementById('tax-display').textContent = 'Rp ' + Math.round(tax).toLocaleString('id-ID');
    document.getElementById('total-display').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
}
</script>

@endsection