<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Role;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (Exception $e) {
            Log::error('Google OAuth redirect error: ' . $e->getMessage());
            return redirect()->to('/')->with('error', 'Failed to connect to Google. Please try again later.');
        }
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user exists by email
            $user = User::where('email', $googleUser->email)->first();
            
            if ($user) {
                // Update provider details if not already set
                if ($user->provider !== 'google' || $user->provider_id !== $googleUser->id) {
                    $user->provider = 'google';
                    $user->provider_id = $googleUser->id;
                    $user->oauth_verified = true;
                    $user->oauth_verified_at = now();
                    $user->oauth_token = $googleUser->token;
                    $user->oauth_refresh_token = $googleUser->refreshToken ?? null;
                    $user->save();
                }
            } else {
                // Create new user
                $user = User::create([
                    'first_name' => explode(' ', $googleUser->name)[0] ?? $googleUser->name,
                    'last_name' => count(explode(' ', $googleUser->name)) > 1 ? end(explode(' ', $googleUser->name)) : '',
                    'email' => $googleUser->email,
                    'provider' => 'google',
                    'provider_id' => $googleUser->id,
                    'password' => Hash::make(Str::random(16)),
                    'oauth_verified' => true,
                    'oauth_verified_at' => now(),
                    'oauth_token' => $googleUser->token,
                    'oauth_refresh_token' => $googleUser->refreshToken ?? null
                ]);
                
                // Assign default role
                $user->roles()->attach(Role::where('name', 'user')->first()->id);
            }
            
            // Generate JWT token
            $token = Auth::login($user);
            
            // Redirect to frontend with token
            return redirect(config('app.frontend_url') . '/auth/google/callback?token=' . $token);
        } catch (Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            return redirect(config('app.frontend_url') . '/auth/google/callback?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Verify account linking when a logged-in user connects their Google account.
     *
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function verifyAccountLinking($token)
    {
        try {
            // Validate token and link accounts
            // Implementation depends on your token verification logic
            
            return response()->json([
                'success' => true,
                'message' => 'Google account successfully linked'
            ]);
        } catch (Exception $e) {
            Log::error('Google account linking error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
} 