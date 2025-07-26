@extends('layouts.app')

@section('title', 'Reset Password - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-key text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Reset Password</h1>
            <p class="text-gray-600">Enter your email to receive reset instructions</p>
        </div>

        <form method="POST" action="#" id="resetForm">
            @csrf
            
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" name="email" id="email" required 
                       value="{{ old('email') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Enter your email address">
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium">
                <i class="fas fa-paper-plane mr-2"></i>
                Send Reset Link
            </button>
        </form>

        <!-- Back to Login -->
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-1"></i>Back to Login
            </a>
        </div>

        <!-- Info -->
        <div class="mt-8 p-4 bg-blue-50 rounded-lg">
            <div class="flex">
                <i class="fas fa-info-circle text-blue-600 mt-1 mr-2"></i>
                <div class="text-sm text-blue-800">
                    <p class="font-medium mb-1">Password reset is currently not configured.</p>
                    <p>Please contact support or use Google login for now.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection