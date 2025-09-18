<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user already exists
            $existingUser = User::where('email', $googleUser->getEmail())->first();
            
            if ($existingUser) {
                // Update Google ID if not set
                if (!$existingUser->google_id) {
                    $existingUser->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar()
                    ]);
                }
                
                Auth::login($existingUser, true);
                
                // SYNC CART SETELAH LOGIN GOOGLE BERHASIL
                try {
                    $cartController = new \App\Http\Controllers\Frontend\CartController();
                    $cartController->syncCartOnLogin($existingUser->id);
                } catch (\Exception $e) {
                    \Log::error('Cart sync error on Google login: ' . $e->getMessage());
                    // Don't fail login if cart sync fails
                }
                
                return $this->redirectAfterLogin('Welcome back! Your cart has been restored.');
            }
            
            // Create new user
            $newUser = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password' => bcrypt('google-auth-' . uniqid())
            ]);
            
            Auth::login($newUser, true);
            
            // SYNC CART UNTUK USER BARU (mungkin ada guest cart)
            try {
                $cartController = new \App\Http\Controllers\Frontend\CartController();
                $cartController->syncCartOnLogin($newUser->id);
            } catch (\Exception $e) {
                \Log::error('Cart sync error on new Google user: ' . $e->getMessage());
                // Don't fail registration if cart sync fails
            }
            
            return $this->redirectAfterLogin('Welcome! Your account has been created successfully and your cart has been saved.');
            
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
        // SAVE CART TO DATABASE SEBELUM LOGOUT
        if (Auth::check()) {
            try {
                $cartController = new \App\Http\Controllers\Frontend\CartController();
                $cartController->saveSessionCartToDatabase(Auth::id());
            } catch (\Exception $e) {
                \Log::error('Cart save error on Google logout: ' . $e->getMessage());
                // Don't fail logout if cart save fails
            }
        }

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