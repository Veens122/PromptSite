<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

use function Symfony\Component\Clock\now;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate registration input
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Fire registered event
        event(new Registered($user));

        // Generate random 6-digit verification code
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save hashed verification code and timestamp
        $user->update([
            'verification_code' => Hash::make($verificationCode),
            'verification_code_sent_at' => now(),
        ]);

        // Store email in session for verification
        session([
            'verification_email' => $user->email,
            'email' => $user->email, // backup
            'verification_attempts' => 1
        ]);

        try {
            // Send verification email (code + link)
            Mail::to($user->email)->send(
                new EmailVerificationMail($user, $verificationCode)
            );

            // Redirect to verification page with correct route name
            return redirect()->route('email.verify')->with([
                'success' => 'Registration successful! A verification email has been sent to you.',
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            // Delete user if mail fails
            $user->delete();

            // Log the error for debugging
            Log::error('Email verification failed: ' . $e->getMessage());

            return redirect()->back()->with([
                'error' => 'Failed to send verification email. Please try again.',
            ]);
        }
    }
}