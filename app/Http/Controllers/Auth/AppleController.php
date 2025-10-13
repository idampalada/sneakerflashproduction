<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AppleController extends Controller
{
    /**
     * Redirect to Apple OAuth
     */
    public function redirectToApple()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return Socialite::driver('apple')->redirect();
    }

    /**
     * Handle Apple OAuth callback
     */
    public function handleAppleCallback()
    {
        try {
            if (Auth::check()) {
                return redirect('/');
            }

            $appleUser = Socialite::driver('apple')->user();
            
            // Check if user exists
            $user = User::where('email', $appleUser->email)->first();

            if ($user) {
                // User exists, just login
                Auth::login($user, true);
                
                return redirect()->intended('/')->with('success', 'Welcome back!');
            } else {
                // Create new user
                $names = $this->extractNamesFromApple($appleUser);
                
                $user = User::create([
                    'name' => $names['full_name'],
                    'first_name' => $names['first_name'],
                    'last_name' => $names['last_name'],
                    'email' => $appleUser->email,
                    'phone' => null, // Apple doesn't provide phone
                    'password' => Hash::make(Str::random(16)), // Random password
                    'apple_id' => $appleUser->id,
                    'email_verified_at' => now(), // Apple emails are pre-verified
                ]);

                Auth::login($user, true);

                return redirect('/')->with('success', 'Account created successfully! Welcome to SneakerFlash!');
            }

        } catch (\Exception $e) {
            \Log::error('Apple OAuth Error: ' . $e->getMessage());
            
            return redirect()->route('register')
                ->with('error', 'Failed to sign up with Apple. Please try again or use email registration.');
        }
    }

    /**
     * Extract names from Apple user data
     */
    private function extractNamesFromApple($appleUser)
    {
        // Apple might provide name in different formats
        $name = $appleUser->name ?? '';
        $email = $appleUser->email;

        if (empty($name)) {
            // If no name provided, extract from email
            $emailPart = explode('@', $email)[0];
            $name = str_replace(['.', '_', '-'], ' ', $emailPart);
            $name = ucwords($name);
        }

        // Split name into first and last
        $nameParts = explode(' ', trim($name));
        $firstName = $nameParts[0] ?? 'User';
        $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => trim($firstName . ' ' . $lastName)
        ];
    }

    /**
     * Logout (shared with Google controller)
     */
    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        
        return redirect('/')->with('success', 'You have been logged out successfully.');
    }
}