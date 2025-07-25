@extends('layouts.app')

@section('title', 'My Profile - SneakerFlash')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">My Profile</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Profile Info -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Profile Information</h2>
            
            @if(isset($user))
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <p class="text-gray-900">{{ $user->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <p class="text-gray-900">{{ $user->email ?? 'N/A' }}</p>
                    </div>
                </div>
                
                <a href="/profile/edit" 
                   class="mt-6 inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Edit Profile
                </a>
            @else
                <p class="text-gray-500">Profile information not available</p>
            @endif
        </div>

        <!-- Order Statistics -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Order Statistics</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $totalOrders ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Total Orders</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        Rp {{ number_format($totalSpent ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-600">Total Spent</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection