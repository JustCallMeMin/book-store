<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class AuthService
{
    /**
     * Đăng ký người dùng
     */
    public function register(array $data): array
    {
        $validator = $this->validateUserRegistration($data);
        if ($validator->fails()) {
            return ['error' => $validator->errors(), 'status' => 422];
        }
        return $this->createUserAndAssignRole($data);
    }

    /* Helper methods for user registration */
    private function validateUserRegistration(array $data)
    {
        return Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
    }

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
            \Log::error('Registration failed: ' . $e->getMessage());
            return ['error' => 'Registration failed: ' . $e->getMessage(), 'status' => 500];
        }
    }

    private function assignDefaultRole(User $user): void
    {
        $role = Role::firstOrCreate(['name' => 'User']);
        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    /* Helper methods for OTP Redis operations */
    private function storeOtpInRedis(string $email, string $otp, int $ttl = 300): void
    {
        Redis::set($this->getOtpKey($email), $otp);
        Redis::expire($this->getOtpKey($email), $ttl);
    }

    private function getOtpFromRedis(string $email): ?string
    {
        return Redis::get($this->getOtpKey($email));
    }

    private function deleteOtpFromRedis(string $email): void
    {
        Redis::del($this->getOtpKey($email));
    }

    private function getOtpKey(string $email): string
    {
        return "password_reset:{$email}";
    }

    /**
     * Xử lý đăng nhập người dùng với Remember Me
     */
    public function login(array $credentials): array
    {
        $validator = Validator::make($credentials, [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return ['error' => $validator->errors(), 'status' => 422];
        }

        // Lấy trường remember
        $remember = $credentials['remember'] ?? false;

        // Xóa trường remember khỏi credentials trước khi truy vấn
        $credentials = collect($credentials)
            ->except(['remember'])
            ->toArray();

        // Kiểm tra đăng nhập
        if (!$token = auth('api')->attempt($credentials)) {
            return ['error' => 'Invalid credentials', 'status' => 401];
        }

        // Lấy user
        $user = User::with('roles')->where('email', $credentials['email'])->first();
        if (!$user) {
            return ['error' => 'User not found after login', 'status' => 404];
        }

        // Cập nhật trường remember_me trong database
        $user->remember_me = $remember;
        
        // Nếu chọn "Remember Me", tăng thời gian sống của JWT token (30 ngày)
        if ($remember) {
            auth('api')->factory()->setTTL(60 * 24 * 30); // 30 ngày
            // Refresh token với TTL mới
            $token = auth('api')->refresh();
        }
        
        $user->save();

        return [
            'message' => 'User successfully logged in',
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60, // Chuyển từ phút sang giây
            'status' => 200
        ];
    }

    /**
     * Đăng xuất người dùng
     */
    public function logout(): array
    {
        $user = auth('api')->user();
        if ($user) {
            $user->remember_me = false;
            $user->save();
        }
        auth('api')->logout();
        return ['message' => 'Successfully logged out', 'status' => 200];
    }

    /**
     * Lấy thông tin người dùng (Profile)
     */
    public function profile(): array
    {
        $user = auth('api')->user();
        if (!$user) {
            return ['error' => 'User not authenticated', 'status' => 401];
        }
        return [
            'status' => 200,
            'user' => new UserResource($user)
        ];
    }

    /**
     * Cập nhật thông tin người dùng (Profile)
     */
    public function updateProfile(array $data): array
    {
        $user = auth('api')->user();
        if (!$user) {
            return ['error' => 'User not authenticated', 'status' => 401];
        }
        $validator = Validator::make($data, [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
        ]);
        if ($validator->fails()) {
            return ['error' => $validator->errors(), 'status' => 422];
        }
        $user->update($data);
        return [
            'status' => 200,
            'message' => 'Profile updated successfully.',
            'user' => new UserResource($user->fresh())
        ];
    }

    /**
     * Đổi mật khẩu
     */
    public function changePassword(array $data): array
    {
        $validator = Validator::make($data, [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed'
        ]);
        if ($validator->fails()) {
            return ['error' => $validator->errors(), 'status' => 422];
        }
        $user = auth('api')->user();
        if (!$user) {
            return ['error' => 'User not authenticated', 'status' => 401];
        }
        if (!Hash::check($data['current_password'], $user->password)) {
            return ['error' => 'Current password is incorrect', 'status' => 400];
        }
        $user->password = Hash::make($data['new_password']);
        $user->save();
        return [
            'status' => 200,
            'message' => 'Password changed successfully.'
        ];
    }

    /**
     * Refresh token
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
            return ['error' => 'Token refresh failed', 'status' => 401];
        }
    }

    /**
     * Gửi OTP qua email
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
     * Gửi OTP để đặt lại mật khẩu
     */
    public function sendPasswordResetOtp(string $email): array
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return ['error' => 'User not found', 'status' => 404];
        }
        // Tạo mã OTP 6 chữ số
        $otp = rand(100000, 999999);
        $this->storeOtpInRedis($email, $otp);
        // Gửi OTP qua email (sử dụng Mailable tùy chỉnh)
        $this->sendOtpEmail($email, $otp);
        return [
            'message' => 'OTP has been sent to your email.',
            'status' => 200
        ];
    }

    /**
     * Đặt lại mật khẩu bằng OTP
     */
    public function resetPassword(array $data): array
    {
        $validator = Validator::make($data, [
            'email' => 'required|string|email',
            'otp' => 'required|string|min:6|max:6',
            'password' => 'required|string|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            return ['error' => $validator->errors(), 'status' => 422];
        }
        $email = $data['email'];
        $otp = $data['otp'];
        $storedOtp = $this->getOtpFromRedis($email);
        if (!$storedOtp) {
            return ['status' => 400, 'message' => 'OTP không hợp lệ hoặc đã hết hạn.'];
        }
        if ($storedOtp !== $otp) {
            return ['status' => 400, 'message' => 'OTP không hợp lệ.'];
        }
        $user = User::where('email', $email)->first();
        if (!$user) {
            return ['status' => 404, 'message' => 'Email không tồn tại.'];
        }
        $user->password = Hash::make($data['password']);
        $user->save();
        $this->deleteOtpFromRedis($email);
        return ['status' => 200, 'message' => 'Password reset successfully.'];
    }

    /**
     * Tạo phản hồi API chứa thông tin xác thực (Auth Response)
     */
    private function generateAuthResponse(User $user, string $message, int $status, bool $remember = false): array
    {
        // Nếu chọn remember me, tăng TTL của JWT lên 30 ngày
        if ($remember) {
            auth('api')->factory()->setTTL(60 * 24 * 30); // 30 ngày
            $user->remember_me = true;
            $user->save();
        }
        
        $token = JWTAuth::claims($user->getJWTCustomClaims())->fromUser($user);
        
        return [
            'message' => $message,
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60, // Chuyển từ phút sang giây
            'status' => $status
        ];
    }
}
