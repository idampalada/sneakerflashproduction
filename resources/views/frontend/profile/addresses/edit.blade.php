@extends('layouts.app')

@section('title', 'Edit Address - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Edit Address</h1>
            <p class="text-gray-600">Update your delivery address information</p>
        </div>

        <!-- Address Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="{{ route('profile.addresses.update', $address->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Address Label -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Address Label *
                    </label>
                    <div class="flex space-x-4">
                        <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 {{ $address->label === 'Kantor' ? 'border-orange-500 bg-orange-50' : '' }}">
                            <div class="radio-custom"></div>
                            <input type="radio" name="label" value="Kantor" class="hidden" {{ $address->label === 'Kantor' ? 'checked' : '' }}>
                            <span class="text-sm font-medium ml-3">Kantor</span>
                        </label>
                        <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 {{ $address->label === 'Rumah' ? 'border-orange-500 bg-orange-50' : '' }}">
                            <div class="radio-custom"></div>
                            <input type="radio" name="label" value="Rumah" class="hidden" {{ $address->label === 'Rumah' ? 'checked' : '' }}>
                            <span class="text-sm font-medium ml-3">Rumah</span>
                        </label>
                    </div>
                </div>

                <!-- Recipient Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Recipient Name *
                        </label>
                        <input type="text" name="recipient_name" id="recipient_name" required
                               value="{{ old('recipient_name', $address->recipient_name) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="phone_recipient" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone Number *
                        </label>
                        <input type="tel" name="phone_recipient" id="phone_recipient" required
                               value="{{ old('phone_recipient', $address->phone_recipient) }}"
                               placeholder="08xxxxxxxxxx"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
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
                        <input type="hidden" name="province_name" id="province_name" value="{{ $address->province_name }}">
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
                        <input type="hidden" name="city_name" id="city_name" value="{{ $address->city_name }}">
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
                        <input type="hidden" name="district_name" id="district_name" value="{{ $address->district_name }}">
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
                        <input type="hidden" name="sub_district_name" id="sub_district_name" value="{{ $address->sub_district_name }}">
                    </div>

                    <!-- Postal Code Display -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Postal Code
                        </label>
                        <input type="text" id="postal_code_display" readonly
                               value="{{ $address->postal_code }}"
                               class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md" 
                               placeholder="Will be filled automatically">
                        <input type="hidden" name="postal_code" id="postal_code" value="{{ $address->postal_code }}">
                    </div>
                </div>

                <!-- Hidden field for destination_id -->
                <input type="hidden" name="destination_id" id="destination_id" value="{{ $address->destination_id }}">

                <!-- Street Address -->
                <div class="mb-4">
                    <label for="street_address" class="block text-sm font-medium text-gray-700 mb-2">
                        Street Address *
                    </label>
                    <textarea name="street_address" id="street_address" rows="3" required
                              placeholder="Enter complete street address, building name, house number"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('street_address', $address->street_address) }}</textarea>
                </div>

                <!-- Notes (Optional) -->
                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Additional Notes (Optional)
                    </label>
                    <textarea name="notes" id="notes" rows="2"
                              placeholder="e.g., Near landmark, special instructions"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('notes', $address->notes) }}</textarea>
                </div>

                <!-- Set as Primary -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_primary" value="1" 
                               class="mr-3 h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded" 
                               {{ old('is_primary', $address->is_primary) ? 'checked' : '' }}>
                        <span class="text-sm font-medium">Set as primary address</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Primary address will be used as default for checkout</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4">
                    <button type="submit" 
                            class="flex-1 bg-orange-600 text-white py-3 rounded-lg hover:bg-orange-700 transition-colors font-medium">
                        Update Address
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

<!-- Radio Button Styles -->
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

<!-- JavaScript for Hierarchical Location -->
<script>
// Current address data dari server
const currentAddress = {
    province_id: {{ $address->province_id ?? 'null' }},
    city_id: {{ $address->city_id ?? 'null' }},
    district_id: {{ $address->district_id ?? 'null' }},
    sub_district_id: {{ $address->sub_district_id ?? 'null' }},
    province_name: "{{ $address->province_name ?? '' }}",
    city_name: "{{ $address->city_name ?? '' }}",
    district_name: "{{ $address->district_name ?? '' }}",
    sub_district_name: "{{ $address->sub_district_name ?? '' }}"
};

console.log('📍 Current address data:', currentAddress);

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Edit address form loading...');
    
    // Load provinces dan setup cascade
    loadProvinces();
});

// Load provinces menggunakan endpoint yang benar
async function loadProvinces() {
    try {
        console.log('🌍 Loading provinces...');
        
        // Coba beberapa endpoint yang mungkin ada
        const endpoints = [
            '/api/location/provinces',
            '/api/addresses/hierarchical/provinces', 
            '/provinces'
        ];
        
        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint);
                const data = await response.json();
                
                if (data.success && (data.provinces || data.data)) {
                    const provinces = data.provinces || data.data;
                    console.log('✅ Provinces loaded from:', endpoint, provinces.length, 'items');
                    
                    populateProvinces(provinces);
                    
                    // Auto-select current province jika ada
                    if (currentAddress.province_id) {
                        await selectProvince(currentAddress.province_id);
                    }
                    return;
                }
            } catch (e) {
                console.log('❌ Endpoint failed:', endpoint, e.message);
                continue;
            }
        }
        
        console.error('❌ All province endpoints failed');
        
    } catch (error) {
        console.error('❌ Error loading provinces:', error);
    }
}

// Populate province dropdown
function populateProvinces(provinces) {
    const provinceSelect = document.getElementById('province_id');
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    
    provinces.forEach(province => {
        const option = document.createElement('option');
        option.value = province.id;
        option.textContent = province.name || province.label;
        if (province.id == currentAddress.province_id) {
            option.selected = true;
        }
        provinceSelect.appendChild(option);
    });
    
    provinceSelect.disabled = false;
    console.log('📋 Province dropdown populated');
}

// Select province dan load cities
async function selectProvince(provinceId) {
    try {
        console.log('🏙️ Loading cities for province:', provinceId);
        
        const endpoints = [
            `/api/location/cities/${provinceId}`,
            `/api/addresses/hierarchical/cities/${provinceId}`,
            `/cities/${provinceId}`
        ];
        
        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint);
                const data = await response.json();
                
                if (data.success && (data.cities || data.data)) {
                    const cities = data.cities || data.data;
                    console.log('✅ Cities loaded from:', endpoint, cities.length, 'items');
                    
                    populateCities(cities);
                    
                    // Auto-select current city jika ada
                    if (currentAddress.city_id) {
                        await selectCity(currentAddress.city_id);
                    }
                    return;
                }
            } catch (e) {
                console.log('❌ Cities endpoint failed:', endpoint, e.message);
                continue;
            }
        }
        
        console.error('❌ All cities endpoints failed for province:', provinceId);
        
    } catch (error) {
        console.error('❌ Error loading cities:', error);
    }
}

// Populate city dropdown
function populateCities(cities) {
    const citySelect = document.getElementById('city_id');
    citySelect.innerHTML = '<option value="">Select City</option>';
    
    cities.forEach(city => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.name || city.label;
        if (city.id == currentAddress.city_id) {
            option.selected = true;
        }
        citySelect.appendChild(option);
    });
    
    citySelect.disabled = false;
    console.log('📋 City dropdown populated');
}

// Select city dan load districts
async function selectCity(cityId) {
    try {
        console.log('🏘️ Loading districts for city:', cityId);
        
        const endpoints = [
            `/api/location/districts/${cityId}`,
            `/api/addresses/hierarchical/districts/${cityId}`,
            `/districts/${cityId}`
        ];
        
        for (const endpoint of endpoints) {
            try {
                const response = await fetch(endpoint);
                const data = await response.json();
                
                if (data.success && (data.districts || data.data)) {
                    const districts = data.districts || data.data;
                    console.log('✅ Districts loaded from:', endpoint, districts.length, 'items');
                    
                    populateDistricts(districts);
                    
                    // Auto-select current district jika ada
                    if (currentAddress.district_id) {
                        await selectDistrict(currentAddress.district_id);
                    }
                    return;
                }
            } catch (e) {
                console.log('❌ Districts endpoint failed:', endpoint, e.message);
                continue;
            }
        }
        
        console.error('❌ All districts endpoints failed for city:', cityId);
        
    } catch (error) {
        console.error('❌ Error loading districts:', error);
    }
}

// Populate district dropdown
function populateDistricts(districts) {
    const districtSelect = document.getElementById('district_id');
    districtSelect.innerHTML = '<option value="">Select District</option>';
    
    districts.forEach(district => {
        const option = document.createElement('option');
        option.value = district.id;
        option.textContent = district.name || district.label;
        if (district.id == currentAddress.district_id) {
            option.selected = true;
        }
        districtSelect.appendChild(option);
    });
    
    districtSelect.disabled = false;
    console.log('📋 District dropdown populated');
}

// Select district dan load sub-districts
async function selectDistrict(districtId) {
    try {
        console.log('🏠 Loading sub-districts for district:', districtId);
        
        const response = await fetch(`/api/location/sub-districts/${districtId}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            console.log('✅ Sub-districts loaded:', data.data.length, 'items');
            
            populateSubDistricts(data.data);
            
            // Auto-select current sub-district jika ada
            if (currentAddress.sub_district_id) {
                selectSubDistrict(currentAddress.sub_district_id);
            }
            return;
        } else {
            console.error('❌ Failed to load sub-districts:', data);
        }
        
    } catch (error) {
        console.error('❌ Error loading sub-districts:', error);
    }
}

// Populate sub-district dropdown
function populateSubDistricts(subDistricts) {
    const subDistrictSelect = document.getElementById('sub_district_id');
    subDistrictSelect.innerHTML = '<option value="">Select Sub District</option>';
    
    subDistricts.forEach(subDistrict => {
        const option = document.createElement('option');
        option.value = subDistrict.id;
        option.textContent = subDistrict.name || subDistrict.label;
        if (subDistrict.id == currentAddress.sub_district_id) {
            option.selected = true;
        }
        subDistrictSelect.appendChild(option);
    });
    
    subDistrictSelect.disabled = false;
    console.log('📋 Sub-district dropdown populated');
}

// Select sub-district dan update postal code
function selectSubDistrict(subDistrictId) {
    const subDistrictSelect = document.getElementById('sub_district_id');
    const selectedOption = subDistrictSelect.querySelector(`option[value="${subDistrictId}"]`);
    
    if (selectedOption) {
        selectedOption.selected = true;
        
        // Update postal code jika ada data zip_code
        // Untuk sekarang, keep postal code yang ada
        console.log('📮 Sub-district selected:', subDistrictId);
    }
}

// Event listeners untuk perubahan dropdown
document.getElementById('province_id').addEventListener('change', function() {
    const provinceId = this.value;
    const provinceName = this.options[this.selectedIndex].text;
    
    if (provinceId) {
        document.getElementById('province_name').value = provinceName;
        selectProvince(provinceId);
        
        // Reset downstream
        resetDropdown('city_id', 'Loading cities...');
        resetDropdown('district_id', 'Select city first...');
        resetDropdown('sub_district_id', 'Select district first...');
    }
});

document.getElementById('city_id').addEventListener('change', function() {
    const cityId = this.value;
    const cityName = this.options[this.selectedIndex].text;
    
    if (cityId) {
        document.getElementById('city_name').value = cityName;
        selectCity(cityId);
        
        // Reset downstream
        resetDropdown('district_id', 'Loading districts...');
        resetDropdown('sub_district_id', 'Select district first...');
    }
});

document.getElementById('district_id').addEventListener('change', function() {
    const districtId = this.value;
    const districtName = this.options[this.selectedIndex].text;
    
    if (districtId) {
        document.getElementById('district_name').value = districtName;
        selectDistrict(districtId);
        
        // Reset downstream
        resetDropdown('sub_district_id', 'Loading sub-districts...');
    }
});

document.getElementById('sub_district_id').addEventListener('change', function() {
    const subDistrictId = this.value;
    const subDistrictName = this.options[this.selectedIndex].text;
    
    if (subDistrictId) {
        document.getElementById('sub_district_name').value = subDistrictName;
        selectSubDistrict(subDistrictId);
    }
});

// Helper function
function resetDropdown(selectId, placeholder) {
    const select = document.getElementById(selectId);
    select.innerHTML = `<option value="">${placeholder}</option>`;
    select.disabled = true;
}
</script>
@endsection