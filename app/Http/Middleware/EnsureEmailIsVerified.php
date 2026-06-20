<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->is_verified) {

            // Store email so the verification page knows the user
            session(['verification_email' => $user->email]);

            return redirect()->route('email.verify')
                ->with('error', 'Please verify your email to continue.');
        }

        return $next($request);
    }
}