{{-- File: resources/views/frontend/checkout/index.blade.php - COMPLETE NO TAX VERSION + VOUCHER SUPPORT --}}
@extends('layouts.app')

@section('title', 'Checkout - SneakerFlash')

@section('content')
<meta name="midtrans-client-key" content="{{ config('services.midtrans.client_key') }}">
<meta name="midtrans-production" content="{{ config('services.midtrans.is_production') ? 'true' : 'false' }}">
<meta name="cart-subtotal" content="{{ $subtotal ?? 0 }}">
<meta name="total-weight" content="{{ $totalWeight ?? 1000 }}">
<meta name="user-authenticated" content="{{ Auth::check() ? 'true' : 'false' }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="user-has-primary-address" content="{{ $userHasPrimaryAddress ? 'true' : 'false' }}">
<meta name="primary-address-id" content="{{ $primaryAddressId ?? 'null' }}">
<meta name="authenticated-user-name" content="{{ $authenticatedUserName ?? '' }}">
<meta name="authenticated-user-phone" content="{{ $authenticatedUserPhone ?? '' }}">
<meta name="store-origin-city" content="{{ env('STORE_ORIGIN_CITY_NAME', 'Jakarta') }}">

{{-- CRITICAL FIX: Add user email meta tag --}}
@if(Auth::check())
    <meta name="user-email" content="{{ Auth::user()->email }}">
@endif
@if($appliedVoucher ?? false)
    <meta name="applied-voucher-code" content="{{ $appliedVoucher['voucher_code'] }}">
    <meta name="applied-voucher-discount" content="{{ $appliedVoucher['discount_amount'] }}">
@endif
@php
    $primaryAddressId = null;
    $userHasPrimaryAddress = false;
    
    if(Auth::check()) {
        $primaryAddressQuery = \App\Models\UserAddress::where('user_id', Auth::id())
                                    ->where('is_primary', true)
                                    ->where('is_active', true)
                                    ->first();
        if($primaryAddressQuery) {
            $primaryAddressId = $primaryAddressQuery->id;
            $userHasPrimaryAddress = true;
        }
    }
    
    $authenticatedUserName = Auth::check() ? Auth::user()->name : '';
    $authenticatedUserPhone = Auth::check() ? Auth::user()->phone : '';
    $authenticatedUserEmail = Auth::check() ? Auth::user()->email : '';
@endphp

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
                <div class="step-title">Delivery Address</div>
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

        <!-- Connection Status -->
        <div id="connection-status" class="mb-4 p-3 rounded-lg border hidden">
            <span id="status-text"></span>
        </div>

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
                            
                            <!-- Gender Selection -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                <div class="flex space-x-4">
                                    <label class="flex items-center">
                                        <input type="radio" name="gender" value="mens" class="mr-2" 
                                               {{ old('gender', Auth::user()->gender ?? '') == 'mens' ? 'checked' : '' }}>
                                        <span class="text-sm">Mens</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="gender" value="womens" class="mr-2" 
                                               {{ old('gender', Auth::user()->gender ?? '') == 'womens' ? 'checked' : '' }}>
                                        <span class="text-sm">Womens</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="gender" value="kids" class="mr-2" 
                                               {{ old('gender', Auth::user()->gender ?? '') == 'kids' ? 'checked' : '' }}>
                                        <span class="text-sm">Kids</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Name Fields - FIXED: Better auto-fill -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First name *</label>
                                    <input type="text" name="first_name" id="first_name" required
                                           value="{{ old('first_name', Auth::check() ? (explode(' ', Auth::user()->name ?? '', 2)[0] ?? '') : '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last name *</label>
                                    <input type="text" name="last_name" id="last_name" required
                                           value="{{ old('last_name', Auth::check() ? (explode(' ', Auth::user()->name ?? '', 2)[1] ?? '') : '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <!-- Email - FIXED: Proper auto-fill -->
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="email" id="email" required
                                       value="{{ old('email', $authenticatedUserEmail ?? (Auth::user()->email ?? '')) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <!-- Phone - FIXED: Proper auto-fill -->
                            <div class="mb-4">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                                <input type="tel" name="phone" id="phone" required
                                       value="{{ old('phone', $authenticatedUserPhone ?? (Auth::user()->phone ?? '')) }}"
                                       placeholder="08xxxxxxxxxx"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <!-- Birthdate -->
                            <div class="mb-4">
                                <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-2">
                                    <span class="text-gray-400">Optional</span> Birthdate
                                </label>
                                <input type="date" name="birthdate" id="birthdate"
                                       value="{{ old('birthdate', Auth::user()->birthdate ? Auth::user()->birthdate->format('Y-m-d') : '') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            @if(!Auth::check())
                                <!-- Create Account Option -->
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="create_account" id="create_account" value="1" 
                                               onchange="togglePassword()" class="mr-3" {{ old('create_account') ? 'checked' : '' }}>
                                        <span class="text-sm font-medium">Create an account (optional)</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">Save time on your next order!</p>
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
                            
                            <!-- Newsletter -->
                            <div class="mb-4">
                                <label class="flex items-start">
                                    <input type="checkbox" name="newsletter_subscribe" value="1" class="mr-3 mt-1" {{ old('newsletter_subscribe') ? 'checked' : '' }}>
                                    <div>
                                        <span class="text-sm font-medium">Sign up for our newsletter</span>
                                        <p class="text-xs text-gray-500 italic">*Get exclusive offers and early discounts*</p>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Privacy Policy -->
                            <div class="mb-6">
                                <label class="flex items-start">
                                    <input type="checkbox" 
                                           name="privacy_accepted" 
                                           id="privacy_accepted" 
                                           value="1"
                                           required 
                                           class="mr-3 mt-1"
                                           {{ old('privacy_accepted') ? 'checked' : '' }}>
                                    <div>
                                        <span class="text-sm font-medium">Customer data privacy *</span>
                                        <p class="text-xs text-gray-500 italic">*I agree to the processing of my personal data and accept the privacy policy.*</p>
                                    </div>
                                </label>
                            </div>
                            
                            <button type="button" onclick="nextStep(2)" 
                                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                Continue
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Delivery Address - UPDATED WITH ADDRESS INTEGRATION -->
                    <div class="checkout-section hidden" id="section-2">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Delivery Address</h2>
                            
                            @if(Auth::check() && $userAddresses->count() > 0)
                                <!-- Existing Addresses for Logged In Users -->
                                <div class="mb-6">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Select Saved Address</h3>
                                    <div class="space-y-3" id="saved-addresses">
                                        @foreach($userAddresses as $address)
                                            <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ $address->is_primary ? 'border-orange-500 bg-orange-50' : 'border-gray-200' }}" 
                                                   data-address-id="{{ $address->id }}">
                                                <input type="radio" 
                                                       name="saved_address_id" 
                                                       value="{{ $address->id }}" 
                                                       class="mt-1 mr-4" 
                                                       {{ $address->is_primary ? 'checked' : '' }}
                                                       data-address-id="{{ $address->id }}"
                                                       onchange="loadSavedAddress(this.dataset.addressId)">
                                                <div class="flex-1">
                                                    <div class="flex items-center mb-2">
                                                        <span class="font-medium text-gray-900">{{ $address->label }}</span>
                                                        @if($address->is_primary)
                                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                                Primary
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-gray-700">
                                                        <p class="font-medium">{{ $address->recipient_name }}</p>
                                                        <p>{{ $address->phone_recipient }}</p>
                                                        <p>{{ $address->street_address }}</p>
                                                        <p class="text-gray-500">{{ $address->location_string }}</p>
                                                    </div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                    
                                    <!-- Add New Address Option -->
                                    <label class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors mt-3">
                                        <input type="radio" name="saved_address_id" value="new" class="mr-4" onchange="showNewAddressForm()">
                                        <div class="text-center w-full">
                                            <svg class="w-6 h-6 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            <span class="text-sm font-medium text-gray-700">Add New Address</span>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Divider -->
                                <div class="relative mb-6">
                                    <div class="absolute inset-0 flex items-center">
                                        <div class="w-full border-t border-gray-300"></div>
                                    </div>
                                    <div class="relative flex justify-center text-sm">
                                        <span class="px-2 bg-white text-gray-500">Or use different address</span>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- New Address Form -->
                            <div id="new-address-form" class="{{ Auth::check() && $userAddresses->count() > 0 ? 'hidden' : '' }}">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    {{ Auth::check() && $userAddresses->count() > 0 ? 'New Address Details' : 'Delivery Address Details' }}
                                </h3>
                                
                                <!-- Address Label with Radio Buttons -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        Address Label *
                                    </label>
                                    <div class="flex space-x-4">
                                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                            <input type="radio" 
                                                   name="address_label" 
                                                   value="Kantor" 
                                                   class="sr-only" 
                                                   {{ old('address_label') == 'Kantor' ? 'checked' : '' }}
                                                   onchange="updateAddressLabelStyles()">
                                            <div class="radio-custom mr-3"></div>
                                            <span class="text-sm font-medium text-gray-700">Kantor</span>
                                        </label>
                                        
                                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                            <input type="radio" 
                                                   name="address_label" 
                                                   value="Rumah" 
                                                   class="sr-only" 
                                                   {{ old('address_label', 'Rumah') == 'Rumah' ? 'checked' : '' }}
                                                   onchange="updateAddressLabelStyles()">
                                            <div class="radio-custom mr-3"></div>
                                            <span class="text-sm font-medium text-gray-700">Rumah</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Recipient Name -->
                                <div class="mb-4">
                                    <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Recipient Name *
                                    </label>
                                    <input type="text" name="recipient_name" id="recipient_name" required
                                           value="{{ old('recipient_name', $authenticatedUserName) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                </div>

                                <!-- Phone Recipient -->
                                <div class="mb-4">
                                    <label for="phone_recipient" class="block text-sm font-medium text-gray-700 mb-2">
                                        Recipient Phone Number *
                                    </label>
                                    <input type="tel" name="phone_recipient" id="phone_recipient" required
                                           value="{{ old('phone_recipient', $authenticatedUserPhone) }}"
                                           placeholder="08xxxxxxxxxx"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    <p class="text-xs text-gray-500 mt-1">Phone number for the recipient (can be different from your account phone)</p>
                                </div>

                                <!-- Location Search -->
                                <div class="mb-4">
                                    <label for="location_search" class="block text-sm font-medium text-gray-700 mb-2">
                                        Search Location *
                                        <span class="text-xs text-gray-500">(Province, City, Subdistrict, Postal Code)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="location_search" 
                                               placeholder="e.g., kebayoran lama, jakarta selatan"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                               autocomplete="off">
                                        
                                        <!-- Search Results -->
                                        <div id="location-results" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            <!-- Results will be populated here -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Location Display -->
                                <div id="selected-location" class="hidden mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-green-800">Selected Location:</h4>
                                            <p id="selected-location-text" class="text-sm text-green-700"></p>
                                        </div>
                                        <button type="button" onclick="clearLocation()" class="text-red-600 hover:text-red-800 text-sm">
                                            Change
                                        </button>
                                    </div>
                                </div>

                                <!-- Hidden Location Fields -->
                                <input type="hidden" name="province_name" id="province_name" required>
                                <input type="hidden" name="city_name" id="city_name" required>
                                <input type="hidden" name="subdistrict_name" id="subdistrict_name" required>
                                <input type="hidden" name="postal_code" id="postal_code" required>
                                <input type="hidden" name="destination_id" id="destination_id">

                                <!-- Street Address -->
                                <div class="mb-4">
                                    <label for="street_address" class="block text-sm font-medium text-gray-700 mb-2">
                                        Street Address *
                                        <span class="text-xs text-gray-500">(Street Name, Building, House Number)</span>
                                    </label>
                                    <textarea name="street_address" id="street_address" rows="3" required
                                              placeholder="Enter complete street address, building name, house number"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('street_address') }}</textarea>
                                </div>

                                @if(Auth::check())
                                    <!-- Save Address Option -->
                                    <div class="mb-4">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="save_address" value="1" 
                                                   class="mr-3 h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded" 
                                                   {{ old('save_address', 'checked') ? 'checked' : '' }}>
                                            <span class="text-sm font-medium">Save this address to my account</span>
                                        </label>
                                        <p class="text-xs text-gray-500 mt-1">You can use this address for future orders</p>
                                    </div>

                                    <!-- Set as Primary Option -->
                                    <div class="mb-4">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="set_as_primary" value="1" 
                                                   class="mr-3 h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded" 
                                                   {{ old('set_as_primary') ? 'checked' : '' }}>
                                            <span class="text-sm font-medium">Set as primary address</span>
                                        </label>
                                        <p class="text-xs text-gray-500 mt-1">Primary address will be used as default for future checkouts</p>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Keep legacy fields for backward compatibility -->
                            <input type="hidden" name="address" id="legacy_address">
                            <input type="hidden" name="destination_label" id="legacy_destination_label">
                            
                            <div class="flex space-x-4 mt-8">
                                <button type="button" onclick="prevStep(1)" 
                                        class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                                    Previous
                                </button>
                                <button type="button" onclick="nextStep(3)" id="continue-step-2"
                                        class="flex-1 bg-orange-600 text-white py-3 rounded-lg hover:bg-orange-700 transition-colors font-medium">
                                    Continue
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Shipping Method -->
                    <div class="checkout-section hidden" id="section-3">
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Shipping Method</h2>
                            <p class="text-sm text-gray-600 mb-4">Package weight: <strong>{{ $totalWeight ?? 1000 }}g</strong></p>
                            
                            <div id="shipping-loading" class="hidden p-4 text-center">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                                <p class="text-sm text-gray-600 mt-2">Calculating shipping options...</p>
                            </div>
                            
                            <div id="shipping-options" class="space-y-3 min-h-[150px]">
                                <div class="p-4 text-center text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
                                    <p>📍 Please select your delivery location first</p>
                                </div>
                            </div>
                            
                            <input type="hidden" name="shipping_method" id="shipping_method" required>
                            <input type="hidden" name="shipping_cost" id="shipping_cost" value="0" required>
                            <!-- Voucher Hidden Inputs -->
<input type="hidden" name="applied_voucher_code" id="applied_voucher_code" value="{{ $appliedVoucher['voucher_code'] ?? '' }}">
<input type="hidden" name="applied_voucher_discount" id="applied_voucher_discount" value="{{ $appliedVoucher['discount_amount'] ?? 0 }}">
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
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Payment Method</h2>
                            <p class="text-sm text-gray-600 mb-6">Choose your preferred payment method. All payments are processed securely through Midtrans.</p>
                            
                            <div class="space-y-3 mb-6">
                                <!-- Online Payment Option -->
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 border-blue-200 bg-blue-50">
                                    <input type="radio" name="payment_method" value="credit_card" checked class="mr-4">
                                    <div class="flex-1">
                                        <div class="font-medium text-blue-800">Online Payment</div>
                                        <div class="text-sm text-blue-600">Credit Card, Bank Transfer, E-Wallet, and more via Midtrans</div>
                                        <div class="flex items-center mt-2 space-x-2">
                                            <span class="text-xs bg-white px-2 py-1 rounded border">💳 Credit Card</span>
                                            <span class="text-xs bg-white px-2 py-1 rounded border">🏦 Bank Transfer</span>
                                            <span class="text-xs bg-white px-2 py-1 rounded border">📱 E-Wallet</span>
                                            <span class="text-xs bg-white px-2 py-1 rounded border">+ More</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- Payment Info -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">Secure Payment</h3>
                                        <p class="text-sm text-blue-700 mt-1">
                                            All online payments are processed securely through Midtrans. 
                                            You'll see all available payment methods after clicking "Continue to Payment".
                                        </p>
                                    </div>
                                </div>
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
                                    Continue to Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary - NO TAX VERSION + VOUCHER SUPPORT -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                        
                        <!-- Cart Items Display - FIXED: Show actual cart items -->
                        <div class="space-y-3 mb-4">
                            @if(isset($cartItems) && $cartItems->count() > 0)
                                @foreach($cartItems as $item)
                                    @php
                                        // Clean product name display
                                        $originalName = $item['name'] ?? 'Unknown Product';
                                        $skuParent = $item['sku_parent'] ?? '';
                                        
                                        $cleanProductName = $originalName;
                                        if (!empty($skuParent)) {
                                            $cleanProductName = preg_replace('/\s*-\s*' . preg_quote($skuParent, '/') . '\s*/', '', $cleanProductName);
                                            $cleanProductName = preg_replace('/\s*' . preg_quote($skuParent, '/') . '\s*/', '', $cleanProductName);
                                        }
                                        
                                        // Remove size patterns
                                        $cleanProductName = preg_replace('/\s*-\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanProductName);
                                        $cleanProductName = preg_replace('/\s*Size\s+[A-Z0-9.]+\s*$/i', '', $cleanProductName);
                                        $cleanProductName = preg_replace('/\s*-\s*(XS|S|M|L|XL|XXL|XXXL|[0-9]+|[0-9]+\.[0-9]+)\s*$/i', '', $cleanProductName);
                                        
                                        $cleanProductName = trim($cleanProductName, ' -');
                                        
                                        // Size information
                                        $itemSize = 'One Size';
                                        if (isset($item['size']) && !empty($item['size'])) {
                                            if (is_array($item['size'])) {
                                                $itemSize = $item['size'][0] ?? 'One Size';
                                            } else {
                                                $itemSize = (string) $item['size'];
                                            }
                                        } elseif (isset($item['product_options']['size'])) {
                                            $itemSize = $item['product_options']['size'] ?? 'One Size';
                                        }
                                        
                                        $hasValidSize = !empty($itemSize) && 
                                                      $itemSize !== 'One Size' && 
                                                      $itemSize !== 'Default' &&
                                                      !is_array($itemSize);

                                        // Image URL with proper fallback
                                        $imageUrl = asset('images/default-product.jpg');
                                        if (!empty($item['image'])) {
                                            $imagePath = $item['image'];
                                            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                                                $imageUrl = $imagePath;
                                            } elseif (str_starts_with($imagePath, '/storage/')) {
                                                $imageUrl = config('app.url') . $imagePath;
                                            } elseif (str_starts_with($imagePath, 'products/')) {
                                                $imageUrl = config('app.url') . '/storage/' . $imagePath;
                                            } elseif (str_starts_with($imagePath, 'assets/') || str_starts_with($imagePath, 'images/')) {
                                                $imageUrl = asset($imagePath);
                                            } else {
                                                $imageUrl = config('app.url') . '/storage/products/' . $imagePath;
                                            }
                                        }
                                    @endphp
                                    
                                    <div class="flex items-center space-x-3 order-summary-item" 
                                         data-name="{{ $cleanProductName }}"
                                         data-quantity="{{ $item['quantity'] ?? 1 }}"
                                         data-price="{{ $item['price'] ?? 0 }}"
                                         data-subtotal="{{ $item['subtotal'] ?? 0 }}"
                                         data-size="{{ $hasValidSize ? $itemSize : '' }}"
                                         data-image="{{ $imageUrl }}">
                                        <img src="{{ $imageUrl }}" 
                                             alt="{{ $cleanProductName }}" 
                                             class="w-12 h-12 object-cover rounded"
                                             onerror="this.src='{{ asset('images/default-product.jpg') }}'">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $cleanProductName }}</p>
                                            @if($hasValidSize)
                                                <p class="text-xs text-gray-500">Size: {{ $itemSize }}</p>
                                            @endif
                                            <p class="text-sm text-gray-500">Qty: {{ $item['quantity'] ?? 1 }}</p>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">
                                            Rp {{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                            @else
                                <div class="p-4 text-center text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
                                    <p>No items in cart</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- VOUCHER/COUPON SECTION - NEW -->
                        <div class="border-t border-gray-200 pt-4 mb-4">
    <h4 class="text-sm font-medium text-gray-900 mb-3">🎫 Voucher Code</h4>
    
    <!-- Voucher Message Container -->
    <div id="voucher-message-container" class="hidden"></div>
    
    <!-- Applied Voucher Display -->
    <div id="applied-voucher-container" class="hidden mb-4"></div>
    
    <!-- Voucher Input Section -->
    <div id="voucher-input-section" class="mb-4">
        <div class="flex space-x-2">
            <input type="text" 
                   id="voucher-code" 
                   placeholder="Enter voucher code"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   maxlength="50">
            <button type="button" 
                    id="apply-voucher-btn"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors font-medium">
                Apply
            </button>
        </div>
        <div id="voucher-validation-message" class="hidden"></div>
    </div>
    
    <!-- Available Vouchers Display -->
    <div id="available-vouchers-container" class="hidden"></div>
</div>

<!-- POINTS EXCHANGE SECTION - NEW -->
@if(Auth::check() && (Auth::user()->points_balance ?? 0) > 0)
<div class="border-t border-gray-200 pt-4 mb-4">
    <h4 class="text-sm font-medium text-gray-900 mb-3">🪙 Tukar Poin</h4>
    
    <!-- Points Message Container -->
    <div id="points-message-container" class="hidden"></div>
    
    <!-- Points Exchange Toggle -->
    <div class="flex items-center justify-between p-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors points-toggle-container">
        <div class="flex items-center">
            <input type="checkbox" 
                   id="use-points-toggle" 
                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                   data-user-points="{{ Auth::user()->points_balance ?? 0 }}">
            <div class="ml-3">
                <label for="use-points-toggle" class="text-sm font-medium text-gray-900 cursor-pointer">
                    Tukarkan <strong>{{ number_format(Auth::user()->points_balance ?? 0, 0, ',', '.') }} Point</strong>
                </label>
                <p class="text-xs text-gray-500">
                    Hemat Rp {{ number_format(Auth::user()->points_balance ?? 0, 0, ',', '.') }} dengan menggunakan poin Anda
                </p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm font-semibold text-green-600">
                -Rp {{ number_format(Auth::user()->points_balance ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-xs text-gray-500">Potongan</div>
        </div>
    </div>
    
    <!-- Points Details (Hidden by default) -->
    <div id="points-details" class="hidden mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-start">
            <svg class="w-4 h-4 text-yellow-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-xs text-yellow-800">
                <p class="font-medium">Cara Kerja Penukaran Poin:</p>
                <ul class="mt-1 space-y-1">
                    <li>• 1 Poin = Rp 1 potongan harga</li>
                    <li>• Poin dapat dikombinasikan dengan voucher</li>
                    <li>• Poin yang digunakan tidak dapat dikembalikan</li>
                    <li>• Maksimal penggunaan: {{ number_format(Auth::user()->points_balance ?? 0, 0, ',', '.') }} poin</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Points Input Section (Custom Amount) -->
    <div id="points-input-section" class="hidden mt-3">
        <div class="flex space-x-2">
            <input type="number" 
                   id="points-amount" 
                   placeholder="Masukkan jumlah poin"
                   min="1"
                   max="{{ Auth::user()->points_balance ?? 0 }}"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <button type="button" 
                    id="apply-points-btn"
                    class="px-4 py-2 bg-yellow-600 text-white text-sm rounded-md hover:bg-yellow-700 transition-colors font-medium">
                Terapkan
            </button>
        </div>
        <div class="mt-2 flex justify-between text-xs text-gray-600">
            <span>Tersedia: {{ number_format(Auth::user()->points_balance ?? 0, 0, ',', '.') }} poin</span>
            <button type="button" id="use-all-points" class="text-blue-600 hover:text-blue-800 font-medium">
                Gunakan Semua
            </button>
        </div>
    </div>
    
    <!-- Applied Points Display -->
    <div id="applied-points-container" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-green-800">
                        <span id="used-points-amount">0</span> poin digunakan
                    </p>
                    <p class="text-xs text-green-700">
                        Potongan: Rp <span id="points-discount-amount">0</span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    id="remove-points-btn"
                    class="text-red-600 hover:text-red-800 text-sm font-medium">
                Hapus
            </button>
        </div>
    </div>
    
    <!-- Hidden Inputs for Points -->
    <input type="hidden" name="points_used" id="points_used" value="{{ $pointsUsed ?? 0 }}">
    <input type="hidden" name="points_discount" id="points_discount" value="{{ $pointsDiscount ?? 0 }}">
</div>
@endif

{{-- Update Order Totals section untuk menyertakan Points Discount --}}
<!-- Order Totals - WITH POINTS SUPPORT -->
<div class="border-t border-gray-200 pt-4 space-y-2">
    <div class="flex justify-between text-sm">
        <span class="text-gray-600">Subtotal</span>
        <span class="text-gray-900" data-subtotal-display>
            Rp {{ number_format($subtotal ?? 0, 0, ',', '.') }}
        </span>
    </div>
    
    <!-- Voucher Discount Row -->
    <div class="discount-row flex justify-between text-sm {{ ($discountAmount ?? 0) > 0 ? '' : 'hidden' }}">
        <span class="text-gray-600">Voucher Discount 
            @if($appliedVoucher ?? false)
                <span class="text-xs text-green-600">({{ $appliedVoucher['voucher_code'] }})</span>
            @endif
        </span>
        <span class="text-green-600 font-medium" data-discount-display>
            -Rp {{ number_format($discountAmount ?? 0, 0, ',', '.') }}
        </span>
    </div>
    
    <!-- Points Discount Row - NEW -->
    <div class="points-discount-row flex justify-between text-sm {{ ($pointsDiscount ?? 0) > 0 ? '' : 'hidden' }}">
        <span class="text-gray-600">Points Discount</span>
        <span class="text-yellow-600 font-medium" data-points-discount-display>
            -Rp {{ number_format($pointsDiscount ?? 0, 0, ',', '.') }}
        </span>
    </div>
    
    <!-- Shipping Cost Row -->
    <div class="flex justify-between text-sm">
        <span class="text-gray-600">Shipping</span>
        <span class="text-gray-900" id="shipping-cost-display" data-shipping-display>
            To be calculated
        </span>
    </div>
    
    <!-- Total Row -->
    <div class="border-t border-gray-200 pt-2">
        <div class="flex justify-between text-base font-medium">
            <span class="text-gray-900">Total</span>
            <span class="text-gray-900" id="total-display" data-total-display>
                Rp {{ number_format(($subtotal ?? 0) - ($discountAmount ?? 0) - ($pointsDiscount ?? 0), 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>
                        
                       

                        <!-- Order Info - Additional helpful info -->
                        @if(isset($cartItems) && $cartItems->count() > 0)
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex justify-between text-xs text-gray-500 mb-2">
                                    <span>Total Items:</span>
                                    <span>{{ $cartItems->count() }} product(s)</span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mb-2">
                                    <span>Total Quantity:</span>
                                    <span>{{ $cartItems->sum('quantity') }} pcs</span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>Estimated Weight:</span>
                                    <span>{{ number_format($totalWeight ?? 1000) }}g</span>
                                </div>
                            </div>
                        @endif

                        <!-- Voucher Benefits Info -->
                        @if($appliedVoucher ?? false)
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="bg-green-50 p-3 rounded-lg">
            <div class="flex items-center text-green-800 text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <strong>{{ $appliedVoucher['voucher_code'] }} Applied!</strong>
            </div>
            <p class="text-xs text-green-700 mt-1">{{ $appliedVoucher['summary'] ?? 'Discount applied to your order' }}</p>
            <p class="text-xs text-green-600 mt-1 font-medium">You saved: Rp {{ number_format($discountAmount ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>
@endif

                        <!-- Security Info -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-center text-xs text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Secure checkout
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Custom Radio Button Styles -->
<style>
.radio-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    position: relative;
    background: white;
    transition: all 0.2s ease;
}

.radio-custom::after {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ea580c;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    transition: transform 0.2s ease;
}

input[type="radio"]:checked + .radio-custom {
    border-color: #ea580c;
}

input[type="radio"]:checked + .radio-custom::after {
    transform: translate(-50%, -50%) scale(1);
}

label:has(input[name="address_label"]:checked) {
    border-color: #ea580c !important;
    background-color: #fff7ed !important;
}

/* Saved Address Selection Styles */
label:has(input[name="saved_address_id"]:checked) {
    border-color: #ea580c !important;
    background-color: #fff7ed !important;
}

.step {
    flex: 1;
    text-align: center;
    position: relative;
}

.step.active .step-number {
    background-color: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.step.completed .step-number {
    background-color: #10b981;
    color: white;
    border-color: #10b981;
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
    transition: all 0.3s ease;
}

.step-title {
    font-size: 0.875rem;
    color: #6b7280;
    transition: all 0.3s ease;
}

.step.active .step-title {
    color: #3b82f6;
    font-weight: 600;
}

.step.completed .step-title {
    color: #10b981;
    font-weight: 500;
}

.checkout-section.active {
    display: block !important;
}

#location-results .search-result-item {
    padding: 12px;
    cursor: pointer;
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s ease;
}

#location-results .search-result-item:hover {
    background-color: #f3f4f6;
}

#location-results .search-result-item:last-child {
    border-bottom: none;
}

.shipping-option {
    transition: all 0.2s ease;
}

.shipping-option:hover {
    background-color: #f8fafc;
    border-color: #3b82f6;
}

.shipping-option input[type="radio"]:checked + .shipping-content {
    border-color: #3b82f6;
    background-color: #eff6ff;
}
</style>

<!-- Script to ensure Order Summary updates properly WITHOUT TAX + VOUCHER SUPPORT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Log Order Summary initialization
    console.log('📊 Order Summary DOM elements loaded - NO TAX VERSION + VOUCHER SUPPORT');
    
    // Get initial values from server-side data
    const initialSubtotal = {{ $subtotal ?? 0 }};
    const initialWeight = {{ $totalWeight ?? 1000 }};
    const cartItemsCount = {{ isset($cartItems) ? $cartItems->count() : 0 }};
    const initialDiscount = {{ $discountAmount ?? 0 }};
    
    console.log('📊 Initial Order Summary data (NO TAX + VOUCHER):', {
        subtotal: initialSubtotal,
        weight: initialWeight,
        itemsCount: cartItemsCount,
        discount: initialDiscount,
        taxRemoved: true,
        voucherSupport: true
    });
    
    // Store in meta tags for JavaScript access
    if (!document.querySelector('meta[name="cart-subtotal"]')) {
        const subtotalMeta = document.createElement('meta');
        subtotalMeta.name = 'cart-subtotal';
        subtotalMeta.content = initialSubtotal;
        document.head.appendChild(subtotalMeta);
    }
    
    if (!document.querySelector('meta[name="total-weight"]')) {
        const weightMeta = document.createElement('meta');
        weightMeta.name = 'total-weight';
        weightMeta.content = initialWeight;
        document.head.appendChild(weightMeta);
    }
    
    // Add discount meta tag for voucher support
    if (!document.querySelector('meta[name="discount-amount"]')) {
        const discountMeta = document.createElement('meta');
        discountMeta.name = 'discount-amount';
        discountMeta.content = initialDiscount;
        document.head.appendChild(discountMeta);
    }
    
    // Validate that totals are showing correctly
    const subtotalElements = document.querySelectorAll('[data-subtotal-display]');
    const totalElements = document.querySelectorAll('[data-total-display]');
    const discountElements = document.querySelectorAll('[data-discount-display]');
    
    if (subtotalElements.length === 0) {
        console.warn('⚠️ No subtotal display elements found');
    }
    
    if (totalElements.length === 0) {
        console.warn('⚠️ No total display elements found');
    }
    
    console.log('🎫 Discount elements found:', discountElements.length);
    
    // Check if cart items are displayed
    const orderSummaryItems = document.querySelectorAll('.order-summary-item');
    console.log('📦 Order Summary items found:', orderSummaryItems.length);
    
    if (orderSummaryItems.length !== cartItemsCount) {
        console.warn('⚠️ Mismatch between cart items count and displayed items:', {
            expected: cartItemsCount,
            displayed: orderSummaryItems.length
        });
    }
    
    // CRITICAL: Log that tax has been completely removed and voucher support added
    console.log('✅ TAX COMPLETELY REMOVED from checkout system');
    console.log('✅ VOUCHER/COUPON SUPPORT ADDED to checkout system');
});
</script>

<!-- Load the NO TAX JavaScript file -->
<script src="{{ asset('js/enhanced-checkout.js') }}"></script>

<script src="{{ asset('js/voucher-checkout.js') }}"></script>

<script src="{{ asset('js/points-checkout.js') }}"></script>
@if(Auth::check())
    <meta name="user-points-balance" content="{{ Auth::user()->points_balance ?? 0 }}">
    @if(($appliedPoints ?? false) && ($pointsUsed ?? 0) > 0)
        <meta name="applied-points-used" content="{{ $pointsUsed }}">
        <meta name="applied-points-discount" content="{{ $pointsDiscount }}">
    @endif
@endif
@endsection