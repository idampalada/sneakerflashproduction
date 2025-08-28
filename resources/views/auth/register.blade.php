@extends('layouts.app')

@section('title', 'Register - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-plus text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Join SneakerFlash</h1>
            <p class="text-gray-600">Create your account and start shopping</p>
        </div>

        <!-- Google Register Button -->
        <div class="mb-6">
            <a href="{{ route('auth.google') }}" 
               class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm bg-white hover:bg-gray-50 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span class="text-gray-700 font-medium">Sign up with Google</span>
            </a>
        </div>

        <!-- Divider -->
        <div class="relative mb-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">Or create account with email</span>
            </div>
        </div>

        <!-- Register Form -->
        <form method="POST" action="{{ route('register.submit') }}" id="registerForm">
            @csrf
            
            {{-- PERUBAHAN 1: Ganti Full Name dengan First Name + Last Name --}}
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input type="text" name="first_name" id="first_name" required 
                           value="{{ old('first_name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="First name">
                    @error('first_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                    <input type="text" name="last_name" id="last_name" required 
                           value="{{ old('last_name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Last name">
                    @error('last_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" name="email" id="email" required 
                       value="{{ old('email') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Enter your email">
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- PERUBAHAN 2: Tambah Phone Number --}}
            <div class="mb-4">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="tel" name="phone" id="phone" required 
                       value="{{ old('phone') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="08xxxxxxxxxx">
                @error('phone')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- PERUBAHAN 3: Fix Password Toggle --}}
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10"
                           placeholder="Create a password">
                    <button type="button" onclick="togglePassword('password')" 
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
            
            {{-- PERUBAHAN 4: Fix Password Confirmation Toggle --}}
            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10"
                           placeholder="Confirm your password">
                    <button type="button" onclick="togglePassword('password_confirmation')" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye" id="confirmEye"></i>
                    </button>
                </div>
                @error('password_confirmation')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Terms & Privacy -->
            <div class="mb-6">
                <label class="flex items-start">
                    <input type="checkbox" name="terms" required class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">
                        I agree to the 
                        <a href="#" class="text-blue-600 hover:text-blue-800">Terms of Service</a>
                        and 
                        <a href="#" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                    </span>
                </label>
                @error('terms')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium">
                <i class="fas fa-user-plus mr-2"></i>
                Create Account
            </button>
        </form>

        <!-- Login Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Already have an account? 
                <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">Sign in</a>
            </p>
        </div>

        <!-- Benefits -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Join SneakerFlash and get:</h3>
            <div class="space-y-2">
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    <span>Exclusive member discounts</span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    <span>Early access to new releases</span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    <span>Free shipping on orders over Rp 500,000</span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    <span>Order tracking and history</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Fixed Password Toggle Function
function togglePassword(fieldId) {
    console.log('togglePassword called with:', fieldId); // Debug log
    
    const passwordInput = document.getElementById(fieldId);
    const eyeIcon = document.getElementById(fieldId === 'password' ? 'passwordEye' : 'confirmEye');
    
    console.log('Elements found:', {
        passwordInput: !!passwordInput,
        eyeIcon: !!eyeIcon,
        eyeIconId: fieldId === 'password' ? 'passwordEye' : 'confirmEye'
    }); // Debug log
    
    if (passwordInput && eyeIcon) {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
            console.log('Password shown'); // Debug log
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
            console.log('Password hidden'); // Debug log
        }
    } else {
        console.error('Password toggle error: Elements not found');
    }
}

// Wait for DOM to load before adding event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded'); // Debug log
    
    // Password confirmation validation
    const passwordConfirmInput = document.getElementById('password_confirmation');
    if (passwordConfirmInput) {
        passwordConfirmInput.addEventListener('input', function() {
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

    // Auto-focus on first name input
    const firstNameInput = document.getElementById('first_name');
    if (firstNameInput) {
        firstNameInput.focus();
    }
    
    // Test if toggle buttons work
    const toggleButtons = document.querySelectorAll('button[onclick*="togglePassword"]');
    console.log('Toggle buttons found:', toggleButtons.length);
});
</script>
@endpush
