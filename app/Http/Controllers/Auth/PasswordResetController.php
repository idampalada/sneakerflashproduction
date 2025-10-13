<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Show forgot password form
     */
    public function showResetForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send password reset email
     */
    public function sendResetEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'Email tidak ditemukan di sistem kami.'
        ]);

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return back()->withErrors([
                'email' => 'Email tidak ditemukan.'
            ]);
        }

        // Generate reset token
        $token = Str::random(60);
        
        // Save token to database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // Send reset email
        $this->sendPasswordResetEmail($user, $token);

        return back()->with('status', 'Link reset password telah dikirim ke email Anda!');
    }

    /**
     * Show password reset form with token
     */
    public function showResetPasswordForm(Request $request, $token)
    {
        $email = $request->query('email');
        
        // Validate token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord || !Hash::check($token, $resetRecord->token)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Token reset password tidak valid atau sudah expired.']);
        }

        // Check if token is expired (1 hour)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Token reset password sudah expired. Silakan request ulang.']);
        }

        return view('auth.passwords.reset', compact('token', 'email'));
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.exists' => 'Email tidak ditemukan.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // Validate token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return back()->withErrors(['email' => 'Token reset tidak valid.']);
        }

        // Check if token is expired
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'Token reset sudah expired.']);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Success message
        return redirect()->route('login')
            ->with('success', 'Password berhasil direset! Silakan login dengan password baru.');
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($user, $token)
    {
        $resetUrl = route('password.reset.form', [
            'token' => $token,
            'email' => $user->email
        ]);

        Mail::send('emails.password-reset', [
            'user' => $user,
            'resetUrl' => $resetUrl,
            'token' => $token
        ], function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Reset Password SneakerFlash');
        });
    }
}