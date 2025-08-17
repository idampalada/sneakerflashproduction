{{-- File: resources/views/frontend/profile/addresses/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Add New Address - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center mb-4">
                <a href="{{ route('profile.addresses.index') }}" 
                   class="text-gray-600 hover:text-gray-800 mr-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Add New Address</h1>
            </div>
            <p class="text-gray-600">Add a new address to your profile for faster checkout.</p>
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

        <!-- Address Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="{{ route('profile.addresses.store') }}" method="POST" id="address-form">
                @csrf
                
                <!-- Address Label with Radio Buttons -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Address Label *
                    </label>
                    <div class="flex space-x-4">
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ old('label') == 'Kantor' ? 'border-orange-500 bg-orange-50' : '' }}">
                            <input type="radio" 
                                   name="label" 
                                   value="Kantor" 
                                   class="sr-only" 
                                   {{ old('label') == 'Kantor' ? 'checked' : '' }}
                                   onchange="updateRadioStyles()">
                            <div class="radio-custom mr-3"></div>
                            <span class="text-sm font-medium text-gray-700">Kantor</span>
                        </label>
                        
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ old('label', 'Rumah') == 'Rumah' ? 'border-orange-500 bg-orange-50' : '' }}">
                            <input type="radio" 
                                   name="label" 
                                   value="Rumah" 
                                   class="sr-only" 
                                   {{ old('label', 'Rumah') == 'Rumah' ? 'checked' : '' }}
                                   onchange="updateRadioStyles()">
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
                           value="{{ old('recipient_name', Auth::user()->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>

                <!-- Phone Recipient -->
                <div class="mb-4">
                    <label for="phone_recipient" class="block text-sm font-medium text-gray-700 mb-2">
                        Recipient Phone Number *
                    </label>
                    <input type="tel" name="phone_recipient" id="phone_recipient" required
                           value="{{ old('phone_recipient', Auth::user()->phone) }}"
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

                <!-- Set as Primary -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_primary" value="1" 
                               class="mr-3 h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded" 
                               {{ old('is_primary') ? 'checked' : '' }}>
                        <span class="text-sm font-medium">Set as primary address</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Primary address will be used as default for checkout</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4">
                    <button type="submit" 
                            class="flex-1 bg-orange-600 text-white py-3 rounded-lg hover:bg-orange-700 transition-colors font-medium">
                        Save Address
                    </button>
                    <a href="{{ route('profile.addresses.index') }}" 
                       class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 transition-colors font-medium text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
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

label:has(input[type="radio"]:checked) {
    border-color: #ea580c !important;
    background-color: #fff7ed !important;
}
</style>

<!-- Location Search JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationSearch = document.getElementById('location_search');
    const locationResults = document.getElementById('location-results');
    const selectedLocation = document.getElementById('selected-location');
    const selectedLocationText = document.getElementById('selected-location-text');
    
    let searchTimeout;

    // Set default label to "Rumah" if none selected
    if (!document.querySelector('input[name="label"]:checked')) {
        document.querySelector('input[name="label"][value="Rumah"]').checked = true;
    }
    updateRadioStyles();

    locationSearch.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            locationResults.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            searchLocation(query);
        }, 300);
    });

    async function searchLocation(query) {
        try {
            const response = await fetch(`/checkout/search-destinations?search=${encodeURIComponent(query)}&limit=10`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const data = await response.json();
                displayLocationResults(data.data || []);
            } else {
                console.error('Location search failed:', response.status);
                locationResults.classList.add('hidden');
            }
        } catch (error) {
            console.error('Location search error:', error);
            locationResults.classList.add('hidden');
        }
    }

    function displayLocationResults(locations) {
        if (locations.length === 0) {
            locationResults.classList.add('hidden');
            return;
        }

        locationResults.innerHTML = '';
        
        locations.forEach(location => {
            const item = document.createElement('div');
            item.className = 'p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0';
            item.innerHTML = `
                <div class="font-medium text-gray-900">${location.display_name || location.subdistrict_name}</div>
                <div class="text-sm text-gray-600">${location.full_address || location.label}</div>
            `;
            
            item.addEventListener('click', () => selectLocation(location));
            locationResults.appendChild(item);
        });

        locationResults.classList.remove('hidden');
    }

    function selectLocation(location) {
        // Fill hidden fields
        document.getElementById('province_name').value = location.province_name || '';
        document.getElementById('city_name').value = location.city_name || '';
        document.getElementById('subdistrict_name').value = location.subdistrict_name || '';
        document.getElementById('postal_code').value = location.zip_code || location.postal_code || '';
        document.getElementById('destination_id').value = location.location_id || location.destination_id || '';

        // Display selected location
        selectedLocationText.textContent = location.full_address || location.label || `${location.subdistrict_name}, ${location.city_name}, ${location.province_name}`;
        selectedLocation.classList.remove('hidden');
        
        // Hide search results
        locationResults.classList.add('hidden');
        
        // Clear search input
        locationSearch.value = '';
    }

    window.clearLocation = function() {
        // Clear hidden fields
        document.getElementById('province_name').value = '';
        document.getElementById('city_name').value = '';
        document.getElementById('subdistrict_name').value = '';
        document.getElementById('postal_code').value = '';
        document.getElementById('destination_id').value = '';
        
        // Hide selected location
        selectedLocation.classList.add('hidden');
        
        // Focus back to search
        locationSearch.focus();
    };

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!locationSearch.contains(e.target) && !locationResults.contains(e.target)) {
            locationResults.classList.add('hidden');
        }
    });
});

function updateRadioStyles() {
    const radioInputs = document.querySelectorAll('input[name="label"]');
    const labels = document.querySelectorAll('label:has(input[name="label"])');
    
    labels.forEach(label => {
        const input = label.querySelector('input[name="label"]');
        if (input.checked) {
            label.classList.add('border-orange-500', 'bg-orange-50');
            label.classList.remove('border-gray-300');
        } else {
            label.classList.remove('border-orange-500', 'bg-orange-50');
            label.classList.add('border-gray-300');
        }
    });
}
</script>

<!-- CSRF Token for AJAX -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection