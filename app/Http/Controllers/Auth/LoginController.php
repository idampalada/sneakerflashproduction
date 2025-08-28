<?php
// File: app/Http/Controllers/Auth/LoginController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    /**
     * Handle user login with email verification check.
     */
    public function login(Request $request)
    {
        if (Auth::check()) {
            return redirect('/');
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Cek apakah user dengan email ini ada
        $user = User::where('email', $credentials['email'])->first();
        
        if ($user) {
            // Cek apakah password benar
            if (!Auth::validate($credentials)) {
                return back()->withErrors([
                    'email' => 'These credentials do not match our records.',
                ])->withInput($request->except('password'));
            }
            
            // Password benar, tapi cek apakah email sudah diverifikasi
            if (!$user->hasVerifiedEmail()) {
                // Email belum diverifikasi, berikan pesan error khusus
                return back()->withErrors([
                    'email' => 'Akun Anda belum diaktivasi.'
                ])->withInput($request->except('password'))
                  ->with('unverified_email', $credentials['email'])
                  ->with('show_verification_help', true);
            }
        }

        // Login normal jika email sudah verified atau user tidak ditemukan (akan error di Auth::attempt)
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Redirect to intended URL or home
            $intendedUrl = session()->pull('url.intended', '/');
            
            // If there are items in cart, redirect to checkout
            $cart = session('cart', []);
            if (!empty($cart) && $intendedUrl === '/') {
                $intendedUrl = '/checkout';
            }

            return redirect($intendedUrl)->with('success', 'Welcome back!');
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->withInput($request->except('password'));
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out successfully.');
    }
}