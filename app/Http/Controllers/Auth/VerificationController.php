<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerificationController extends Controller
{
    /**
     * Show email verification notice
     */
    public function notice()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->hasVerifiedEmail()) {
            return redirect('/');
        }

        return view('auth.verify-email');
    }

    /**
     * Handle email verification
     */
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        
        return redirect('/')->with('success', 'Email verified successfully! Welcome to SneakerFlash!');
    }

    /**
     * Resend verification email
     */
    public function resend(Request $request)
    {
        if (Auth::check()) {
            // Jika user sudah login, kirim verification langsung
            $user = Auth::user();
            
            if ($user->email_verified_at !== null) {
                return back()->with('message', 'Email already verified.');
            }
            
            $user->sendEmailVerificationNotification();
            return back()->with('message', 'Verification link sent to your email!');
        }

        // Jika user belum login, validate email dulu
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'Email tidak ditemukan di sistem kami.'
        ]);
        
        $user = User::where('email', $request->email)->first();
        
        if ($user) {
            if ($user->email_verified_at !== null) {
                return back()->withErrors([
                    'email' => 'Email sudah diverifikasi. Anda bisa login langsung.'
                ]);
            }
            
            // Login sementara untuk kirim verification
            Auth::login($user);
            $user->sendEmailVerificationNotification();
            
            return redirect()->route('verification.notice')
                ->with('success', 'Email aktivasi telah dikirim ke ' . $request->email);
        }
        
        return back()->withErrors([
            'email' => 'Email tidak ditemukan.'
        ]);
    }
}