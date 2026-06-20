<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    /**
     * Show the email verification page.
     */
    public function verifyEmail(Request $request)
    {
        // Get email from session with multiple fallbacks
        $email = session('verification_email') ?? session('email') ?? old('email');

        // If no email found, redirect to login
        if (!$email) {
            return redirect('/login')->with('error', 'Please log in to verify your email.');
        }

        // Verify the email exists in database
        if (!User::where('email', $email)->exists()) {
            // Clear invalid email from session
            session()->forget(['verification_email', 'email']);
            return redirect('/register')->with('error', 'Email not found. Please register first.');
        }

        // Store email in session for future requests
        session(['verification_email' => $email]);

        return view('auth.verify-email', compact('email'));
    }

    /**
     * Verify using code
     */
    public function verifyWithCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'verification_code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        // Store email in session
        session(['verification_email' => $request->email]);

        // Check if code is expired (30 minutes)
        if (!$user->verification_code_sent_at || $user->verification_code_sent_at->addMinutes(30)->isPast()) {
            return back()->withInput()->withErrors([
                'verification_code' => 'The verification code has expired. Please request a new one.'
            ])->with('email', $request->email);
        }

        // Verify code
        if (Hash::check($request->verification_code, $user->verification_code)) {
            // Clear verification session data
            session()->forget(['verification_email', 'email', 'verification_attempts']);

            $user->update([
                'is_verified' => true,
                'email_verified_at' => now(),
                'verification_code' => null,
                'verification_code_sent_at' => null,
            ]);

            Auth::login($user);

            return redirect('/')->with('success', 'Email verified successfully!');
        }

        return back()->withInput()->withErrors([
            'verification_code' => 'Invalid verification code.'
        ])->with('email', $request->email);
    }

    /**
     * Verify using link
     */
    public function verifyWithLink($id, $hash)
    {
        $user = User::findOrFail($id);

        // Validate hash
        if (!hash_equals($hash, sha1($user->email))) {
            abort(403, 'Invalid verification link.');
        }

        // If already verified
        if ($user->email_verified_at) {
            return redirect('/')->with('info', 'Email already verified.');
        }

        // Clear verification session data
        session()->forget(['verification_email', 'email', 'verification_attempts']);

        // Verify email
        $user->update([
            'is_verified' => true,
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_sent_at' => null,
        ]);

        Auth::login($user);

        return redirect('/')->with('success', 'Email verified successfully via link!');
    }

    /**
     * Resend verification email
     */
    public function resend(Request $request)
    {
        try {
            // Get email from request or session
            $email = $request->email ?? session('verification_email') ?? session('email') ?? old('email');

            if (!$email) {
                return back()->with('error', 'Email address is required to resend verification.');
            }

            $user = User::where('email', $email)->first();
            if (!$user) {
                return back()->with('error', 'User not found with this email address.');
            }

            // Prevent spamming — allow resend only every 60 seconds
            if ($user->verification_code_sent_at && $user->verification_code_sent_at->diffInSeconds(now()) < 60) {
                $seconds = 60 - $user->verification_code_sent_at->diffInSeconds(now());
                return back()->with('error', "Please wait $seconds seconds before resending the code.");
            }

            // Generate new 6-digit code
            $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Update user with new verification code
            $user->update([
                'verification_code' => Hash::make($verificationCode),
                'verification_code_sent_at' => now(),
            ]);

            // Send verification email
            Mail::to($user->email)->send(new EmailVerificationMail($user, $verificationCode));

            // Store email and increment attempts
            session([
                'verification_email' => $user->email,
                'verification_attempts' => session('verification_attempts', 0) + 1
            ]);

            return back()->with('success', 'A new verification code has been sent to your email.');
        } catch (\Exception $e) {
            Log::error('Resend verification failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to resend verification email. Please try again.');
        }
    }
}
