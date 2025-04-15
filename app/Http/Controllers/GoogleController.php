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
            Log::error('Google OAuth redirect error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
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
                $this->updateUserOAuthDetails($user, $googleUser);
            } else {
                $user = $this->createUserFromGoogle($googleUser);
            }
            
            // Generate JWT token
            $token = Auth::login($user);
            
            // Redirect to frontend with token
            return redirect($this->getRedirectUrl('callback', ['token' => $token]));
        } catch (Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect($this->getRedirectUrl('callback', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Verify account linking when a logged-in user connects their Google account.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function verifyAccountLinking(Request $request)
    {
        try {
            // Validate token and link accounts
            $request->validate([
                'token' => 'required|string',
                'provider_id' => 'required|string'
            ]);
            
            // Đảm bảo người dùng đã đăng nhập
            if (!Auth::check()) {
                throw new Exception('You must be logged in to link accounts.');
            }
            
            $user = Auth::user();
            
            // Cập nhật thông tin OAuth nếu người dùng tồn tại
            if ($user) {
                $user->update([
                    'provider' => 'google',
                    'provider_id' => $request->input('provider_id'),
                    'oauth_verified' => true,
                    'oauth_verified_at' => now(),
                    'oauth_token' => $request->input('token')
                ]);
            } else {
                throw new Exception('User not found.');
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Google account successfully linked'
            ]);
        } catch (Exception $e) {
            Log::error('Google account linking error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Update user OAuth details with Google data
     *
     * @param User $user
     * @param \Laravel\Socialite\Two\User $googleUser
     * @return User
     */
    protected function updateUserOAuthDetails(User $user, $googleUser): User
    {
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
        
        return $user;
    }
    
    /**
     * Create a new user from Google data
     *
     * @param \Laravel\Socialite\Two\User $googleUser
     * @return User
     */
    protected function createUserFromGoogle($googleUser): User
    {
        // Parse name components
        $nameParts = explode(' ', $googleUser->name);
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? end($nameParts) : '';
        
        // Create new user
        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $googleUser->email,
            'provider' => 'google',
            'provider_id' => $googleUser->id,
            'password' => Hash::make(Str::random(24)), // Stronger random password
            'oauth_verified' => true,
            'oauth_verified_at' => now(),
            'oauth_token' => $googleUser->token,
            'oauth_refresh_token' => $googleUser->refreshToken ?? null
        ]);
        
        // Assign default role
        $defaultRole = Role::where('name', 'user')->first();
        if ($defaultRole) {
            $user->roles()->attach($defaultRole->id);
        } else {
            Log::warning('Default user role not found when creating Google user', [
                'email' => $googleUser->email
            ]);
        }
        
        return $user;
    }
    
    /**
     * Get redirect URL for frontend
     *
     * @param string $endpoint
     * @param array $params
     * @return string
     */
    protected function getRedirectUrl(string $endpoint, array $params = []): string
    {
        $baseUrl = config('app.frontend_url') . '/auth/google/' . $endpoint;
        
        if (empty($params)) {
            return $baseUrl;
        }
        
        $query = http_build_query($params);
        return $baseUrl . '?' . $query;
    }
} 