{{-- File: resources/views/frontend/profile/addresses/index.blade.php --}}
@extends('layouts.app')

@section('title', 'My Addresses - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <div class="flex items-center mb-2">
                    <a href="{{ route('profile.index') }}" 
                       class="text-gray-600 hover:text-gray-800 mr-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900">My Addresses</h1>
                </div>
                <p class="text-gray-600">Manage your delivery addresses for faster checkout.</p>
            </div>
            
            <a href="{{ route('profile.addresses.create') }}" 
               class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                + Add New Address
            </a>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Addresses List -->
        @if($addresses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($addresses as $address)
                    <div class="bg-white rounded-lg shadow-md p-6 {{ $address->is_primary ? 'ring-2 ring-blue-500' : '' }}">
                        <!-- Address Header -->
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $address->label ?: 'Address' }}</h3>
                                @if($address->is_primary)
                                    <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Primary
                                    </span>
                                @endif
                            </div>
                            
                            <!-- Action Dropdown -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                    </svg>
                                </button>
                                
                                <div x-show="open" @click.away="open = false" 
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                    <div class="py-1">
                                        <a href="{{ route('profile.addresses.edit', $address->id) }}" 
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Edit Address
                                        </a>
                                        
                                        @if(!$address->is_primary)
                                            <button data-address-id="{{ $address->id }}"
                                                    onclick="setPrimaryAddress(this.dataset.addressId)" 
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Set as Primary
                                            </button>
                                        @endif
                                        
                                        <button data-address-id="{{ $address->id }}" 
                                                data-address-name="{{ $address->label ?: 'this address' }}"
                                                onclick="confirmDeleteAddress(this.dataset.addressId, this.dataset.addressName)" 
                                                class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                            Delete Address
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recipient Info -->
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-gray-700">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="font-medium">{{ $address->recipient_name }}</span>
                            </div>
                            
                            <div class="flex items-center text-gray-700">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <span>{{ $address->phone_recipient }}</span>
                            </div>
                        </div>

                        <!-- Address Details -->
                        <div class="space-y-2">
                            <div class="flex items-start text-gray-700">
                                <svg class="w-4 h-4 mr-2 mt-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm">{{ $address->street_address }}</p>
                                    <p class="text-sm text-gray-500">{{ $address->location_string }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex space-x-2">
                                <a href="{{ route('profile.addresses.edit', $address->id) }}" 
                                   class="flex-1 text-center px-3 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                                    Edit
                                </a>
                                
                                @if(!$address->is_primary)
                                    <button data-address-id="{{ $address->id }}"
                                            onclick="setPrimaryAddress(this.dataset.addressId)" 
                                            class="flex-1 px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                        Set Primary
                                    </button>
                                @else
                                    <div class="flex-1 px-3 py-2 text-sm bg-gray-100 text-gray-500 rounded text-center">
                                        Primary Address
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($addresses->hasPages())
                <div class="mt-8">
                    {{ $addresses->links() }}
                </div>
            @endif

        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-md p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No addresses yet</h3>
                <p class="text-gray-600 mb-6">Add your first address to speed up checkout and delivery.</p>
                <a href="{{ route('profile.addresses.create') }}" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Your First Address
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Address</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete "<span id="deleteAddressName"></span>"? This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmDelete" 
                        class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-700 transition-colors">
                    Delete
                </button>
                <button onclick="closeDeleteModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-24 hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    // Example JavaScript untuk cascading dropdown
async function loadProvinces() {
    const response = await fetch('/api/addresses/provinces');
    const data = await response.json();
    // Populate province dropdown
}

async function loadCities(provinceId) {
    const response = await fetch(`/api/addresses/cities/${provinceId}`);
    const data = await response.json();
    // Populate city dropdown
}

async function loadDistricts(cityId) {
    const response = await fetch(`/api/addresses/districts/${cityId}`);
    const data = await response.json();
    // Populate district dropdown
}

async function loadSubDistricts(districtId) {
    const response = await fetch(`/api/addresses/sub-districts/${districtId}`);
    const data = await response.json();
    // Populate sub-district dropdown
}
let deleteAddressId = null;

function setPrimaryAddress(addressId) {
    if (confirm('Set this as your primary address?')) {
        fetch('/profile/addresses/' + addressId + '/set-primary', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update primary address: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating primary address.');
        });
    }
}

function confirmDeleteAddress(addressId, addressName) {
    deleteAddressId = addressId;
    document.getElementById('deleteAddressName').textContent = addressName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    deleteAddressId = null;
    document.getElementById('deleteModal').classList.add('hidden');
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    if (deleteAddressId) {
        fetch('/profile/addresses/' + deleteAddressId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete address: ' + data.message);
                closeDeleteModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting address.');
            closeDeleteModal();
        });
    }
});

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<!-- CSRF Token for AJAX -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection