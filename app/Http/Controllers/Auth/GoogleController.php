<?php
// File: app/Http/Controllers/Auth/GoogleController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (Exception $e) {
            return redirect('/login')->with('error', 'Unable to connect to Google. Please try again.');
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            // Get user data from Google
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user already exists by Google ID
            $existingUser = User::where('google_id', $googleUser->getId())->first();
            
            if ($existingUser) {
                // Update user info if exists
                $existingUser->update([
                    'name' => $googleUser->getName(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(), // Auto-verify Google users
                ]);
                
                Auth::login($existingUser, true);
                
                return $this->redirectAfterLogin();
            }
            
            // Check if user exists by email
            $existingEmailUser = User::where('email', $googleUser->getEmail())->first();
            
            if ($existingEmailUser) {
                // Link Google account to existing email user
                $existingEmailUser->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
                
                Auth::login($existingEmailUser, true);
                
                return $this->redirectAfterLogin();
            }
            
            // Create new user
            $newUser = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password' => null, // No password for Google users
            ]);
            
            Auth::login($newUser, true);
            
            return $this->redirectAfterLogin('Welcome! Your account has been created successfully.');
            
        } catch (Exception $e) {
            \Log::error('Google OAuth Error: ' . $e->getMessage());
            
            return redirect('/login')->with('error', 'Google login failed. Please try again or use email login.');
        }
    }

    /**
     * Handle user logout
     */
    public function logout()
    {
        Auth::logout();
        
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        
        return redirect('/')->with('success', 'You have been logged out successfully.');
    }

    /**
     * Redirect user after successful login
     */
    private function redirectAfterLogin($message = 'Welcome back!')
    {
        // Check for intended URL
        $intendedUrl = session()->pull('url.intended', '/');
        
        // Redirect to checkout if there are items in cart
        $cart = session('cart', []);
        if (!empty($cart) && $intendedUrl === '/') {
            $intendedUrl = '/checkout';
        }
        
        return redirect($intendedUrl)->with('success', $message);
    }
}