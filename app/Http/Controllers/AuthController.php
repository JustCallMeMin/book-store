<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\RedisActivityService;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected RedisActivityService $activityService;

    public function __construct(
        AuthService $authService,
        RedisActivityService $activityService
    ) {
        $this->authService = $authService;
        $this->activityService = $activityService;
    }

    /**
     * Đăng ký tài khoản mới.
     */
    public function register(Request $request)
    {
        $result = $this->authService->register($request->all());
        
        // Ghi lại hoạt động nếu đăng ký thành công
        if ($result['status'] === 201 && isset($result['user'])) {
            $this->activityService->log(
                $result['user']['id'],
                'register',
                'User registered successfully',
                [],
                $request->ip(),
                $request->userAgent()
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Đăng nhập tài khoản đã có.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);
        
        $result = $this->authService->login($request->all());
        
        // Ghi lại hoạt động nếu đăng nhập thành công
        if ($result['status'] === 200 && isset($result['user'])) {
            $this->activityService->log(
                $result['user']['id'],
                'login',
                'User logged in',
                ['method' => 'credentials'],
                $request->ip(),
                $request->userAgent()
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Đăng xuất người dùng.
     */
    public function logout()
    {
        // Lấy user ID trước khi đăng xuất
        $userId = auth()->id();
        
        $result = $this->authService->logout();
        
        // Ghi lại hoạt động đăng xuất
        if ($userId) {
            $this->activityService->log(
                $userId,
                'logout',
                'User logged out',
                [],
                request()->ip(),
                request()->userAgent()
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Lấy thông tin người dùng.
     */
    public function profile()
    {
        $userId = auth()->id();
        $result = $this->authService->profile();
        
        // Ghi lại hoạt động xem hồ sơ
        if ($userId) {
            $this->activityService->log(
                $userId,
                'view_profile',
                'User viewed their profile',
                [],
                request()->ip(),
                request()->userAgent()
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Cập nhật thông tin người dùng.
     */
    public function updateProfile(Request $request)
    {
        $userId = auth()->id();
        $result = $this->authService->updateProfile($request->all());
        
        // Ghi lại hoạt động cập nhật hồ sơ
        if ($result['status'] === 200 && $userId) {
            $this->activityService->log(
                $userId,
                'update_profile',
                'User updated their profile',
                ['fields' => array_keys($request->except(['password', 'token']))],
                $request->ip(),
                $request->userAgent()
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Đổi mật khẩu.
     */
    public function changePassword(Request $request)
    {
        $userId = auth()->id();
        $result = $this->authService->changePassword($request->all());
        
        // Ghi lại hoạt động đổi mật khẩu
        if ($result['status'] === 200 && $userId) {
            $this->activityService->log(
                $userId,
                'change_password',
                'User changed their password',
                [],
                $request->ip(),
                $request->userAgent()
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Refresh token.
     */
    public function refreshToken()
    {
        return $this->handleResponse($this->authService->refreshToken());
    }

    /**
     * Gửi email quên mật khẩu với OTP.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|string|email']);
        return $this->handleResponse($this->authService->sendPasswordResetOtp($request->email));
    }

    /**
     * Reset mật khẩu bằng OTP.
     */
    public function resetPassword(Request $request)
    {
        return $this->handleResponse($this->authService->resetPassword($request->all()));
    }

    /**
     * Chuẩn hóa response API
     */
    private function handleResponse(array $result)
    {
        return response()->json($result, $result['status']);
    }
}
