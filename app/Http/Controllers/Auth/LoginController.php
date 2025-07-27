<?php
// File: app/Http/Controllers/Auth/LoginController.php
// FIXED VERSION - Same pattern as working RegisterController

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        // Check if user is already logged in (same as RegisterController)
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        // Check if user is already logged in (same as RegisterController)
        if (Auth::check()) {
            return redirect('/');
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

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