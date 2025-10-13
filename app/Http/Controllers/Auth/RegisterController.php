<?php
// File: app/Http/Controllers/Auth/RegisterController.php
// SIMPLE FALLBACK VERSION - No complex middleware

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        // Check if user is already logged in
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.register');
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        if (Auth::check()) {
            return redirect('/');
        }

        // Validate the request
        $request->validate([
            'first_name' => 'required|string|max:255|min:2',
            'last_name' => 'required|string|max:255|min:2',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|min:10|max:20|regex:/^[0-9+\-\s\(\)]{10,}$/|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'required|accepted',
        ], [
            'first_name.required' => 'Please enter your first name.',
            'first_name.min' => 'First name must be at least 2 characters.',
            'last_name.required' => 'Please enter your last name.',
            'last_name.min' => 'Last name must be at least 2 characters.',
            'email.required' => 'Please enter your email address.',
            'email.unique' => 'This email is already registered.',
            'phone.required' => 'Please enter your phone number.',
            'phone.min' => 'Phone number must be at least 10 digits.',
            'phone.regex' => 'Please enter a valid phone number.',
            'phone.unique' => 'This phone number is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'terms.required' => 'You must agree to the Terms of Service.',
        ]);

        $fullName = trim($request->first_name . ' ' . $request->last_name);
        $cleanPhone = preg_replace('/[^0-9+\-\s\(\)]/', '', $request->phone);

        // Create the user (TANPA email_verified_at)
        $user = User::create([
            'name' => $fullName,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $cleanPhone,
            'password' => Hash::make($request->password),
            // HAPUS: 'email_verified_at' => now(), // Biarkan null untuk verification
        ]);

        // Trigger email verification
        event(new Registered($user));

        // JANGAN auto login, redirect ke halaman verifikasi
        return redirect()->route('verification.notice')
            ->with('success', 'Registration successful! Please check your email to verify your account.');
    }
}