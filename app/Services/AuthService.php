<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Default token TTL in minutes
     */
    private const DEFAULT_TOKEN_TTL = 60; // 1 hour
    
    /**
     * Remember me token TTL in minutes
     */
    private const REMEMBER_TOKEN_TTL = 60 * 24 * 30; // 30 days
    
    /**
     * OTP expiration time in seconds
     */
    private const OTP_EXPIRATION = 600; // 10 minutes
    
    /**
     * Register a new user
     *
     * @param array $data Registration data
     * @return array Response with user data or error
     */
    public function register(array $data): array
    {
        $validator = $this->validateUserRegistration($data);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }
        return $this->createUserAndAssignRole($data);
    }

    /**
     * Validate user registration data
     *
     * @param array $data Registration data
     * @return ValidationValidator
     */
    private function validateUserRegistration(array $data): ValidationValidator
    {
        return Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
    }

    /**
     * Create a new user and assign default role
     *
     * @param array $data User data
     * @return array Response with user data or error
     */
    private function createUserAndAssignRole(array $data): array
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'id' => Str::uuid(),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'provider' => null,
                'provider_id' => null,
                'oauth_verified' => false,
                'oauth_verified_at' => null,
                'oauth_token' => null,
                'oauth_refresh_token' => null,
                'remember_token' => null,
            ]);

            $this->assignDefaultRole($user);
            DB::commit();

            return $this->generateAuthResponse($user, 'User successfully registered', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed: ' . $e->getMessage());
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign default role to a user
     *
     * @param User $user
     * @return void
     */
    private function assignDefaultRole(User $user): void
    {
        $role = Role::firstOrCreate(['name' => 'User']);
        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Handle user login with Remember Me functionality
     *
     * @param array $credentials Login credentials
     * @return array Response with user data and token or error
     */
    public function login(array $credentials): array
    {
        $validator = Validator::make($credentials, [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        // Get remember flag
        $remember = $credentials['remember'] ?? false;

        // Remove remember field from credentials before authentication
        $loginCredentials = collect($credentials)
            ->except(['remember'])
            ->toArray();

        // Check login credentials
        if (!$token = auth('api')->attempt($loginCredentials)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        // Get user with roles
        $user = User::with('roles')->where('email', $loginCredentials['email'])->first();
        if (!$user) {
            return $this->errorResponse('User not found after login', 404);
        }

        // Update remember_me field in database
        $user->remember_me = $remember;
        
        // If "Remember Me" is selected, increase JWT token TTL (30 days)
        if ($remember) {
            auth('api')->factory()->setTTL(self::REMEMBER_TOKEN_TTL);
            // Refresh token with new TTL
            $token = auth('api')->refresh();
        }
        
        $user->save();

        return [
            'message' => 'User successfully logged in',
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60, // Convert minutes to seconds
            'status' => 200
        ];
    }

    /**
     * Log out a user
     *
     * @return array Response with logout status
     */
    public function logout(): array
    {
        $user = auth('api')->user();
        if ($user) {
            $user->remember_me = false;
            $user->save();
        }
        auth('api')->logout();
        return $this->successResponse('Successfully logged out', 200);
    }

    /**
     * Get user profile information
     *
     * @return array Response with user data or error
     */
    public function profile(): array
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }
        
        return [
            'status' => 200,
            'user' => new UserResource($user)
        ];
    }

    /**
     * Update user profile information
     *
     * @param array $data Profile data to update
     * @return array Response with updated user data or error
     */
    public function updateProfile(array $data): array
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }
        
        $validator = Validator::make($data, [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }
        
        $user->update($data);
        
        return [
            'status' => 200,
            'message' => 'Profile updated successfully.',
            'user' => new UserResource($user->fresh())
        ];
    }

    /**
     * Change user password
     *
     * @param array $data Password data
     * @return array Response with status
     */
    public function changePassword(array $data): array
    {
        $validator = Validator::make($data, [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed'
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }
        
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }
        
        if (!Hash::check($data['current_password'], $user->password)) {
            return $this->errorResponse('Current password is incorrect', 400);
        }
        
        $user->password = Hash::make($data['new_password']);
        $user->save();
        
        return $this->successResponse('Password changed successfully.', 200);
    }

    /**
     * Refresh JWT token
     *
     * @return array Response with new token or error
     */
    public function refreshToken(): array
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            
            return [
                'status' => 200,
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'message' => 'Token refreshed successfully.'
            ];
        } catch (\Exception $e) {
            Log::error('Token refresh failed: ' . $e->getMessage());
            return $this->errorResponse('Token refresh failed', 401);
        }
    }

    /**
     * Send OTP for password reset
     *
     * @param string $email User email
     * @return array Response with status
     */
    public function sendPasswordResetOtp(string $email): array
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }
        
        // Generate secure 6-digit OTP
        $otp = $this->generateSecureOtp();
        $this->storeOtpInRedis($email, $otp, self::OTP_EXPIRATION);
        
        // Send OTP via email
        $this->sendOtpEmail($email, $otp);
        
        return $this->successResponse('OTP has been sent to your email.', 200);
    }

    /**
     * Generate a secure 6-digit OTP
     * 
     * @return string
     */
    private function generateSecureOtp(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Reset password using OTP
     *
     * @param array $data Reset data with email, OTP and new password
     * @return array Response with status
     */
    public function resetPassword(array $data): array
    {
        $validator = Validator::make($data, [
            'email' => 'required|string|email',
            'otp' => 'required|string|min:6|max:6',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }
        
        $email = $data['email'];
        $otp = $data['otp'];
        $storedOtp = $this->getOtpFromRedis($email);
        
        if (!$storedOtp) {
            return $this->errorResponse('OTP is invalid or has expired.', 400);
        }
        
        if ($storedOtp !== $otp) {
            return $this->errorResponse('OTP is invalid.', 400);
        }
        
        $user = User::where('email', $email)->first();
        if (!$user) {
            return $this->errorResponse('Email does not exist.', 404);
        }
        
        $user->password = Hash::make($data['password']);
        $user->save();
        
        $this->deleteOtpFromRedis($email);
        
        return $this->successResponse('Password reset successfully.', 200);
    }

    /**
     * Store OTP in Redis
     *
     * @param string $email User email
     * @param string $otp OTP code
     * @param int $ttl Time to live in seconds
     * @return void
     */
    private function storeOtpInRedis(string $email, string $otp, int $ttl = self::OTP_EXPIRATION): void
    {
        Redis::set($this->getOtpKey($email), $otp);
        Redis::expire($this->getOtpKey($email), $ttl);
    }

    /**
     * Get OTP from Redis
     *
     * @param string $email User email
     * @return string|null OTP or null if not found
     */
    private function getOtpFromRedis(string $email): ?string
    {
        return Redis::get($this->getOtpKey($email));
    }

    /**
     * Delete OTP from Redis
     *
     * @param string $email User email
     * @return void
     */
    private function deleteOtpFromRedis(string $email): void
    {
        Redis::del($this->getOtpKey($email));
    }

    /**
     * Get Redis key for OTP
     *
     * @param string $email User email
     * @return string Redis key
     */
    private function getOtpKey(string $email): string
    {
        return "password_reset:{$email}";
    }

    /**
     * Send OTP via email
     *
     * @param string $email User email
     * @param string $otp OTP code
     * @return void
     */
    private function sendOtpEmail(string $email, string $otp): void
    {
        $subject = "Your Password Reset OTP";
        $message = "Your OTP code for password reset is: {$otp}. It will expire in 10 minutes.";

        Mail::raw($message, function ($mail) use ($email, $subject) {
            $mail->to($email)->subject($subject);
        });
    }

    /**
     * Generate authentication response
     *
     * @param User $user User model
     * @param string $message Response message
     * @param int $status HTTP status code
     * @param bool $remember Whether to remember the user
     * @return array Response with user data and token
     */
    private function generateAuthResponse(User $user, string $message, int $status, bool $remember = false): array
    {
        // If remember me is selected, increase JWT TTL to 30 days
        if ($remember) {
            auth('api')->factory()->setTTL(self::REMEMBER_TOKEN_TTL);
            $user->remember_me = true;
            $user->save();
        }
        
        $token = JWTAuth::claims($user->getJWTCustomClaims())->fromUser($user);
        
        return [
            'message' => $message,
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60, // Convert minutes to seconds
            'status' => $status
        ];
    }

    /**
     * Generate success response
     *
     * @param string $message Success message
     * @param int $status HTTP status code
     * @param array $additional Additional data to include
     * @return array Response array
     */
    private function successResponse(string $message, int $status = 200, array $additional = []): array
    {
        return array_merge([
            'message' => $message,
            'status' => $status
        ], $additional);
    }

    /**
     * Generate error response
     *
     * @param mixed $error Error message or validator errors
     * @param int $status HTTP status code
     * @return array Response array
     */
    private function errorResponse($error, int $status): array
    {
        return [
            'error' => $error,
            'status' => $status
        ];
    }
}
