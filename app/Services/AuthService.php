<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class AuthService
{
    /**
     * Xử lý đăng ký người dùng
     */
    public function register(array $data)
    {
        $validator = Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ['error' => $validator->errors(), 'status' => 422];
        }

        // Tạo user mới
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
        ]);

        // Gán role mặc định "User"
        $role = Role::where('name', 'User')->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        // Tạo JWT token
        $token = JWTAuth::fromUser($user);

        return [
            'message'      => 'User successfully registered',
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'status'       => 201
        ];
    }

    /**
     * Xử lý đăng nhập người dùng
     */
    public function login(array $credentials)
    {
        $validator = Validator::make($credentials, [
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ['error' => $validator->errors(), 'status' => 422];
        }

        if (!$token = auth('api')->attempt($credentials)) {
            return ['error' => 'Invalid credentials', 'status' => 401];
        }

        $user = auth('api')->user();

        return [
            'message'      => 'User successfully logged in',
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'status'       => 200
        ];
    }

    /**
     * Gửi email quên mật khẩu với OTP
     */
    public function sendPasswordResetOtp($email): array
    {
        // Kiểm tra user có tồn tại không
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'status' => 404,
                'message' => 'User does not exist.'
            ];
        }

        // Tạo OTP 6 chữ số
        $otp = rand(100000, 999999);

        // Lưu OTP vào cache trong 10 phút
        $cacheKey = 'password_reset:' . $email;
        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        // Gửi OTP qua email
        Mail::raw("Your password reset code is: $otp", function ($message) use ($email) {
            $message->to($email)
                ->subject('Password Reset Code');
        });

        return [
            'status' => 200,
            'message' => 'OTP has been sent to your email.'
        ];
    }

    /**
     * Reset mật khẩu bằng OTP
     */
    public function resetPassword(array $data): array
    {
        $validator = Validator::make($data, [
            'email'    => 'required|string|email',
            'otp'      => 'required|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ['error' => $validator->errors(), 'status' => 422];
        }

        $cachedOtp = Cache::get("password_reset:{$data['email']}");
        if (!$cachedOtp || $cachedOtp != $data['otp']) {
            return ['error' => 'Invalid OTP or email.', 'status' => 400];
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return ['error' => 'User does not exist.', 'status' => 404];
        }

        $user->password = Hash::make($data['password']);
        $user->save();
        Cache::forget("password_reset:{$data['email']}");

        return ['message' => 'Password reset successfully.', 'status' => 200];
    }
}
