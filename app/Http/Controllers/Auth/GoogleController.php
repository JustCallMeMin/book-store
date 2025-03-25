<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use App\Models\OAuthVerification;
use App\Notifications\OAuthLinkVerification;

class GoogleController extends Controller
{
    /**
     * Chuyển hướng người dùng đến trang đăng nhập Google
     */
    public function redirectToGoogle()
    {
        try {
            return response()->json([
                'url' => Socialite::driver('google')
                    ->stateless()
                    ->redirect()
                    ->getTargetUrl()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not connect to Google. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xử lý callback từ Google
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Tìm user theo email
            $existingUser = User::where('email', $googleUser->email)->first();

            if ($existingUser) {
                // Nếu user đã tồn tại và chưa có provider
                if (!$existingUser->provider) {
                    // Gửi email xác thực để liên kết tài khoản
                    return $this->handleExistingUserLinking($existingUser, $googleUser);
                }

                // Nếu user đã tồn tại và đã xác thực với Google
                if ($existingUser->provider === 'google' && $existingUser->oauth_verified) {
                    return $this->loginUser($existingUser);
                }
            }

            // Tạo user mới nếu chưa tồn tại
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'provider' => 'google',
                'provider_id' => $googleUser->id,
                'oauth_verified' => true,
                'oauth_verified_at' => now(),
                'oauth_token' => $googleUser->token,
                'oauth_refresh_token' => $googleUser->refreshToken,
                'password' => bcrypt(Str::random(16))
            ]);

            return $this->loginUser($user);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not log in with Google. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xử lý việc liên kết tài khoản hiện có với Google
     */
    private function handleExistingUserLinking(User $user, $googleUser)
    {
        // Tạo bản ghi xác thực OAuth mới
        $verification = OAuthVerification::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => $googleUser->id,
            'token' => OAuthVerification::generateToken(),
            'oauth_token' => $googleUser->token,
            'oauth_refresh_token' => $googleUser->refreshToken,
            'expires_at' => now()->addHours(1), // Token hết hạn sau 1 giờ
        ]);

        // Gửi email xác thực
        $user->notify(new OAuthLinkVerification($verification));

        // Trả về response yêu cầu xác thực email
        return response()->json([
            'status' => 'verification_required',
            'message' => 'Vui lòng kiểm tra email của bạn để xác thực việc liên kết tài khoản.',
            'email' => $user->email
        ], 200);
    }

    /**
     * Xác thực liên kết tài khoản
     */
    public function verifyAccountLinking($provider, $token)
    {
        $verification = OAuthVerification::where('token', $token)
            ->where('provider', $provider)
            ->whereNull('verified_at')
            ->first();

        if (!$verification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token xác thực không hợp lệ hoặc đã hết hạn.'
            ], 400);
        }

        if ($verification->isExpired()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token xác thực đã hết hạn.'
            ], 400);
        }

        // Cập nhật thông tin user
        $user = $verification->user;
        $user->update([
            'provider' => $verification->provider,
            'provider_id' => $verification->provider_id,
            'oauth_token' => $verification->oauth_token,
            'oauth_refresh_token' => $verification->oauth_refresh_token,
            'oauth_verified' => true,
            'oauth_verified_at' => now()
        ]);

        // Đánh dấu xác thực thành công
        $verification->update([
            'verified_at' => now()
        ]);

        return $this->loginUser($user);
    }

    /**
     * Login user và trả về token
     */
    private function loginUser(User $user)
    {
        $token = auth()->login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged in with Google',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }
} 