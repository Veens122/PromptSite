<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Socialite;
use Pest\Support\Str;

class GithubLoginController extends Controller
{
    //
    public function redirectToGithub()
    {
        return Socialite::driver('github')->redirect();
    }

    public function handleGithubCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->user();

            // Check if user already exists
            $user = User::where('email', $githubUser->getEmail())->first();

            if ($user) {
                // If user exists, update their GitHub ID
                $user->update([
                    'github_id' => $githubUser->getId(),
                    'avatar' => $githubUser->getAvatar(),
                    'github_token' => $githubUser->token,
                    'github_refresh_token' => $githubUser->refreshToken,
                ]);
            } else {
                // Create a new user if user doesn't exist
                $user = User::create([
                    'name' => $githubUser->getName() ?? $githubUser->getNickname(),
                    'email' => $githubUser->getEmail(),
                    'github_id' => $githubUser->getId(),
                    'avatar' => $githubUser->getAvatar(),
                    'github_token' => $githubUser->token,
                    'github_refresh_token' => $githubUser->refrehToken,
                    'password' => Hash::make(Str::random(24))
                ]);
            }

            // Log in the user
            Auth::login($user);

            return redirect('/');
        } catch (\Exception $e) {
            Log::error('GitHub OAuth Error: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Unable to login with GitHub. Please try again.');
        }
    }
}
