@extends('layouts.app')

@section('title', 'Login - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-running text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back!</h1>
            <p class="text-gray-600">Sign in to your SneakerFlash account</p>
        </div>

        <!-- Google Login Button -->
        <div class="mb-6">
            <a href="{{ route('auth.google') }}" 
               class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm bg-white hover:bg-gray-50 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span class="text-gray-700 font-medium">Continue with Google</span>
            </a>
        </div>

        <!-- Divider -->
        <div class="relative mb-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">Or continue with email</span>
            </div>
        </div>

        <!-- Email Login Form -->
        <form method="POST" action="{{ route('login.submit') }}" id="loginForm">
            @csrf
            
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" name="email" id="email" required 
                       value="{{ old('email') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Enter your email">
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    {{-- Simple resend link jika akun belum diaktivasi --}}
                    @if(session('show_verification_help') && session('unverified_email'))
                        <p class="text-sm mt-2">
                            <a href="#" onclick="resendVerification('{{ session('unverified_email') }}')" 
                               class="text-blue-600 hover:text-blue-800 underline">
                                Resend activation email
                            </a>
                        </p>
                    @endif
                @enderror
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter your password">
                    <button type="button" onclick="togglePassword()" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800">
                    Forgot password?
                </a>
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Sign In
            </button>
        </form>

        <!-- Register Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Don't have an account? 
                <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-800 font-medium">Create account</a>
            </p>
        </div>

        <!-- Guest Checkout -->
        <div class="mt-4 text-center">
            <a href="{{ route('cart.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-shopping-cart mr-1"></i>Continue as guest
            </a>
        </div>

        <!-- Features -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="grid grid-cols-2 gap-4 text-center">
                <div>
                    <i class="fas fa-shield-alt text-blue-600 text-xl mb-2"></i>
                    <p class="text-xs text-gray-600">Secure Login</p>
                </div>
                <div>
                    <i class="fas fa-truck text-blue-600 text-xl mb-2"></i>
                    <p class="text-xs text-gray-600">Fast Delivery</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}

{{-- Simple resend verification function --}}
function resendVerification(email) {
    if (confirm('Kirim ulang email aktivasi ke ' + email + '?')) {
        // Buat form dan submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/email/resend-verification';
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const emailInput = document.createElement('input');
        emailInput.type = 'hidden';
        emailInput.name = 'email';
        emailInput.value = email;
        
        form.appendChild(csrfInput);
        form.appendChild(emailInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-focus on email input
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('email').focus();
});
</script>
@endpush