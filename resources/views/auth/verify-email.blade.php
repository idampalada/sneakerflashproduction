@extends('layouts.app')

@section('title', 'Verify Email - SneakerFlash')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-envelope text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Verify Your Email</h1>
            <p class="text-gray-600">We sent a verification link to your email address</p>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-800">
                        Before proceeding, please check your email for a verification link. 
                        If you didn't receive the email, we can send another one.
                    </p>
                </div>
            </div>
        </div>

        @if (session('warning'))
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-orange-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-orange-800 font-medium">{{ session('warning') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session('message'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-green-800">{{ session('message') }}</p>
            </div>
        @endif

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium mb-4">
                <i class="fas fa-paper-plane mr-2"></i>
                Resend Verification Email
            </button>
        </form>

        <div class="text-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-600 hover:text-gray-800">
                    Use a different email address
                </button>
            </form>
        </div>

        <!-- Tips -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Tips:</h3>
            <div class="space-y-2">
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    <span>Check your spam/junk folder</span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    <span>Add our email to your contacts</span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                    <i class="fas fa-check text-green-500 mr-2"></i>
                    <span>Email may take up to 5 minutes to arrive</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection