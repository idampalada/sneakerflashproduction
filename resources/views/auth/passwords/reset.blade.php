{{-- File: resources/views/auth/passwords/reset.blade.php --}}

@extends('layouts.app')

@section('title', 'Reset Password - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-lock text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Create New Password</h1>
            <p class="text-gray-600">Enter your new password below</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm">
            @csrf
            
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            
            <div class="mb-4">
                <label for="email_display" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="text" id="email_display" value="{{ $email }}" readonly
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
            </div>
            
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500 pr-10"
                           placeholder="Enter new password">
                    <button type="button" id="togglePasswordBtn" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye" id="passwordEye"></i>
                    </button>
                </div>
                <div class="mt-1">
                    <div class="text-xs text-gray-500">
                        Password must be at least 8 characters long
                    </div>
                </div>
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500 pr-10"
                           placeholder="Confirm new password">
                    <button type="button" id="toggleConfirmBtn" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye" id="confirmEye"></i>
                    </button>
                </div>
                @error('password_confirmation')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <button type="submit" 
                    class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors font-medium">
                <i class="fas fa-key mr-2"></i>
                Reset Password
            </button>
        </form>

        <!-- Back to Login -->
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-1"></i>Back to Login
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle
    const passwordBtn = document.getElementById('togglePasswordBtn');
    if (passwordBtn) {
        passwordBtn.addEventListener('click', function() {
            const input = document.getElementById('password');
            const icon = document.getElementById('passwordEye');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
    
    // Confirm Password toggle
    const confirmBtn = document.getElementById('toggleConfirmBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const input = document.getElementById('password_confirmation');
            const icon = document.getElementById('confirmEye');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    // Password confirmation validation
    const confirmInput = document.getElementById('password_confirmation');
    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
                this.classList.add('border-red-500');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-500');
            }
        });
    }
});
</script>
@endpush