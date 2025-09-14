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
            <form action="{{ route('profile.addresses.store') }}" method="POST" id="addressForm">
                @csrf
                
                <!-- Address Label with Radio Buttons -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Address Label *
                    </label>
                    <div class="flex space-x-4">
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="label" value="Kantor" class="mr-3 text-orange-500">
                            <span class="text-sm font-medium text-gray-700">Kantor</span>
                        </label>
                        
                        <label class="flex items-center p-4 border border-orange-500 bg-orange-50 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="label" value="Rumah" class="mr-3 text-orange-500" checked>
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

                <!-- Hierarchical Location Selection -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Location Selection</h3>
                    
                    <!-- Province -->
                    <div class="mb-4">
                        <label for="province_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Province *
                        </label>
                        <select name="province_id" id="province_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Loading provinces...</option>
                        </select>
                        <input type="hidden" name="province_name" id="province_name">
                    </div>

                    <!-- City -->
                    <div class="mb-4">
                        <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">
                            City/Regency *
                        </label>
                        <select name="city_id" id="city_id" required disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500 disabled:bg-gray-100">
                            <option value="">Select province first...</option>
                        </select>
                        <input type="hidden" name="city_name" id="city_name">
                    </div>

                    <!-- District -->
                    <div class="mb-4">
                        <label for="district_id" class="block text-sm font-medium text-gray-700 mb-2">
                            District *
                        </label>
                        <select name="district_id" id="district_id" required disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500 disabled:bg-gray-100">
                            <option value="">Select city first...</option>
                        </select>
                        <input type="hidden" name="district_name" id="district_name">
                    </div>

                    <!-- Sub District -->
                    <div class="mb-4">
                        <label for="sub_district_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Sub District *
                        </label>
                        <select name="sub_district_id" id="sub_district_id" required disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500 disabled:bg-gray-100">
                            <option value="">Select district first...</option>
                        </select>
                        <input type="hidden" name="sub_district_name" id="sub_district_name">
                    </div>

                    <!-- Postal Code Display -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Postal Code
                        </label>
                        <input type="text" id="postal_code_display" readonly
                               class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" 
                               placeholder="Will be filled automatically">
                        <input type="hidden" name="postal_code" id="postal_code">
                        <p class="text-xs text-gray-500 mt-1">Postal code will be filled automatically</p>
                    </div>
                </div>

                <!-- Hidden field for destination_id -->
                <input type="hidden" name="destination_id" id="destination_id">

                <!-- Street Address -->
                <div class="mb-4">
                    <label for="street_address" class="block text-sm font-medium text-gray-700 mb-2">
                        Street Address *
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

<script>
// Simple JavaScript - No conflicts
let addressData = {
    provinces: [],
    cities: [],
    districts: [],
    subDistricts: []
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Address form loaded');
    
    // Get DOM elements
    const provinceSelect = document.getElementById('province_id');
    const citySelect = document.getElementById('city_id');
    const districtSelect = document.getElementById('district_id');
    const subDistrictSelect = document.getElementById('sub_district_id');
    
    // Load provinces immediately
    loadProvinces();
    
    // Province change handler
    provinceSelect.addEventListener('change', function() {
        const provinceId = this.value;
        const provinceName = this.options[this.selectedIndex].text;
        
        console.log('Province selected:', provinceId, provinceName);
        
        if (provinceId) {
            document.getElementById('province_name').value = provinceName;
            loadCities(provinceId);
            
            // Reset downstream selects
            resetSelect(citySelect, 'Loading cities...');
            resetSelect(districtSelect, 'Select city first...');
            resetSelect(subDistrictSelect, 'Select district first...');
            clearPostalCode();
        } else {
            document.getElementById('province_name').value = '';
            resetSelect(citySelect, 'Select province first...');
            resetSelect(districtSelect, 'Select city first...');
            resetSelect(subDistrictSelect, 'Select district first...');
            clearPostalCode();
        }
    });
    
    // City change handler
    citySelect.addEventListener('change', function() {
        const cityId = this.value;
        const cityName = this.options[this.selectedIndex].text;
        
        console.log('City selected:', cityId, cityName);
        
        if (cityId) {
            document.getElementById('city_name').value = cityName;
            loadDistricts(cityId);
            
            // Reset downstream selects
            resetSelect(districtSelect, 'Loading districts...');
            resetSelect(subDistrictSelect, 'Select district first...');
            clearPostalCode();
        } else {
            document.getElementById('city_name').value = '';
            resetSelect(districtSelect, 'Select city first...');
            resetSelect(subDistrictSelect, 'Select district first...');
            clearPostalCode();
        }
    });
    
    // District change handler
    districtSelect.addEventListener('change', function() {
        const districtId = this.value;
        const districtName = this.options[this.selectedIndex].text;
        
        console.log('District selected:', districtId, districtName);
        
        if (districtId) {
            document.getElementById('district_name').value = districtName;
            loadSubDistricts(districtId);
            
            // Reset sub-district
            resetSelect(subDistrictSelect, 'Loading sub-districts...');
            clearPostalCode();
        } else {
            document.getElementById('district_name').value = '';
            resetSelect(subDistrictSelect, 'Select district first...');
            clearPostalCode();
        }
    });
    
    // Sub-district change handler
    subDistrictSelect.addEventListener('change', function() {
        const subDistrictId = this.value;
        const subDistrictName = this.options[this.selectedIndex].text;
        const zipCode = this.options[this.selectedIndex].getAttribute('data-zip');
        
        console.log('Sub-district selected:', subDistrictId, subDistrictName, zipCode);
        
        if (subDistrictId) {
            document.getElementById('sub_district_name').value = subDistrictName;
            document.getElementById('destination_id').value = subDistrictId;
            
            // Update postal code
            if (zipCode && zipCode !== '0') {
                document.getElementById('postal_code_display').value = zipCode;
                document.getElementById('postal_code').value = zipCode;
            }
        } else {
            document.getElementById('sub_district_name').value = '';
            document.getElementById('destination_id').value = '';
            clearPostalCode();
        }
    });
});

async function loadProvinces() {
    const provinceSelect = document.getElementById('province_id');
    
    try {
        console.log('🔄 Loading provinces...');
        
        const response = await fetch('/api/addresses/provinces');
        const result = await response.json();
        
        console.log('📡 Provinces response:', result);
        
        if (result.success && result.data) {
            addressData.provinces = result.data;
            
            // Clear and populate select
            provinceSelect.innerHTML = '<option value="">Select Province...</option>';
            
            result.data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.id;
                option.textContent = province.name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
            console.log(`✅ Loaded ${result.data.length} provinces`);
        } else {
            throw new Error(result.message || 'Failed to load provinces');
        }
    } catch (error) {
        console.error('❌ Error loading provinces:', error);
        provinceSelect.innerHTML = '<option value="">Error loading provinces</option>';
    }
}

async function loadCities(provinceId) {
    const citySelect = document.getElementById('city_id');
    
    try {
        console.log('🔄 Loading cities for province:', provinceId);
        
        const response = await fetch(`/api/addresses/cities/${provinceId}`);
        const result = await response.json();
        
        console.log('📡 Cities response:', result);
        
        if (result.success && result.data) {
            addressData.cities = result.data;
            
            // Clear and populate select
            citySelect.innerHTML = '<option value="">Select City/Regency...</option>';
            
            result.data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });
            
            citySelect.disabled = false;
            console.log(`✅ Loaded ${result.data.length} cities`);
        } else {
            throw new Error(result.message || 'Failed to load cities');
        }
    } catch (error) {
        console.error('❌ Error loading cities:', error);
        citySelect.innerHTML = '<option value="">Error loading cities</option>';
        citySelect.disabled = false;
    }
}

async function loadDistricts(cityId) {
    const districtSelect = document.getElementById('district_id');
    
    try {
        console.log('🔄 Loading districts for city:', cityId);
        
        const response = await fetch(`/api/addresses/districts/${cityId}`);
        const result = await response.json();
        
        console.log('📡 Districts response:', result);
        
        if (result.success && result.data) {
            addressData.districts = result.data;
            
            // Clear and populate select
            districtSelect.innerHTML = '<option value="">Select District...</option>';
            
            result.data.forEach(district => {
                const option = document.createElement('option');
                option.value = district.id;
                option.textContent = district.name;
                districtSelect.appendChild(option);
            });
            
            districtSelect.disabled = false;
            console.log(`✅ Loaded ${result.data.length} districts`);
        } else {
            throw new Error(result.message || 'Failed to load districts');
        }
    } catch (error) {
        console.error('❌ Error loading districts:', error);
        districtSelect.innerHTML = '<option value="">Error loading districts</option>';
        districtSelect.disabled = false;
    }
}

async function loadSubDistricts(districtId) {
    const subDistrictSelect = document.getElementById('sub_district_id');
    
    try {
        console.log('🔄 Loading sub-districts for district:', districtId);
        
        const response = await fetch(`/api/addresses/sub-districts/${districtId}`);
        const result = await response.json();
        
        console.log('📡 Sub-districts response:', result);
        
        if (result.success && result.data) {
            addressData.subDistricts = result.data;
            
            // Clear and populate select
            subDistrictSelect.innerHTML = '<option value="">Select Sub-District...</option>';
            
            result.data.forEach(subDistrict => {
                const option = document.createElement('option');
                option.value = subDistrict.id;
                option.textContent = subDistrict.name;
                
                // Store zip code in data attribute
                if (subDistrict.zip_code) {
                    option.setAttribute('data-zip', subDistrict.zip_code);
                }
                
                subDistrictSelect.appendChild(option);
            });
            
            subDistrictSelect.disabled = false;
            console.log(`✅ Loaded ${result.data.length} sub-districts`);
        } else {
            throw new Error(result.message || 'Failed to load sub-districts');
        }
    } catch (error) {
        console.error('❌ Error loading sub-districts:', error);
        subDistrictSelect.innerHTML = '<option value="">Error loading sub-districts</option>';
        subDistrictSelect.disabled = false;
    }
}

function resetSelect(selectElement, placeholder) {
    selectElement.innerHTML = `<option value="">${placeholder}</option>`;
    selectElement.disabled = true;
}

function clearPostalCode() {
    document.getElementById('postal_code_display').value = '';
    document.getElementById('postal_code').value = '';
}
</script>

<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection