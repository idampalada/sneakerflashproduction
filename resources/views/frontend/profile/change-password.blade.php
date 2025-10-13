@extends('layouts.app')

@section('title', 'Change Password - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="{{ route('profile.index') }}" class="hover:text-blue-600">Profile</a></li>
                <li>/</li>
                <li class="text-gray-900">Change Password</li>
            </ol>
        </nav>

        <h1 class="text-3xl font-bold text-gray-900 mb-8">Change Password</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
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

        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="{{ route('profile.password.update') }}" method="POST" id="change-password-form">
                @csrf
                @method('PUT')
                
                <!-- Security Notice -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-blue-800">Security Tips</h3>
                            <ul class="text-sm text-blue-700 mt-1 list-disc list-inside">
                                <li>Use at least 8 characters</li>
                                <li>Include uppercase and lowercase letters</li>
                                <li>Add numbers and special characters</li>
                                <li>Avoid common words or personal information</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Current Password -->
                <div class="mb-6">
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Password *
                    </label>
                    <input type="password" 
                           name="current_password" 
                           id="current_password" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('current_password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        New Password *
                    </label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           required
                           minlength="8"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm New Password *
                    </label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation" 
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('password_confirmation')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4">
                    <button type="submit" 
                            class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Change Password
                    </button>
                    <a href="{{ route('profile.index') }}" 
                       class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Security Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h2 class="text-lg font-semibold mb-4">Security Information</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-medium text-gray-900">Account Security</h3>
                    <p class="text-sm text-gray-600">
                        Keep your account secure by using a strong, unique password.
                    </p>
                </div>
                
                <div class="pt-4 border-t">
                    <h3 class="font-medium text-gray-900">Security Tips</h3>
                    <ul class="text-sm text-gray-600 mt-2 space-y-1 list-disc list-inside">
                        <li>Change your password regularly</li>
                        <li>Don't share your password with anyone</li>
                        <li>Use a unique password for this account</li>
                        <li>Log out from shared or public computers</li>
                    </ul>
                </div>
                
                <div class="pt-4 border-t">
                    <h3 class="font-medium text-gray-900">Need Help?</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        If you're having trouble changing your password, 
                        <a href="{{ route('contact') }}" class="text-blue-600 hover:underline">contact our support team</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection