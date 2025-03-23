<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Đăng ký tài khoản mới.
     */
    public function register(Request $request)
    {
        return $this->handleResponse($this->authService->register($request->all()));
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
        
        return $this->handleResponse($this->authService->login($request->all()));
    }

    /**
     * Đăng xuất người dùng.
     */
    public function logout()
    {
        return $this->handleResponse($this->authService->logout());
    }

    /**
     * Lấy thông tin người dùng.
     */
    public function profile()
    {
        return $this->handleResponse($this->authService->profile());
    }

    /**
     * Cập nhật thông tin người dùng.
     */
    public function updateProfile(Request $request)
    {
        return $this->handleResponse($this->authService->updateProfile($request->all()));
    }

    /**
     * Đổi mật khẩu.
     */
    public function changePassword(Request $request)
    {
        return $this->handleResponse($this->authService->changePassword($request->all()));
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
     * Xác thực Remember Me Token
     */
    public function verifyRememberToken(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'remember_token' => 'required|string'
        ]);
        return $this->handleResponse(
            $this->authService->verifyRememberToken($request->email, $request->remember_token)
        );
    }

    /**
     * Chuẩn hóa response API
     */
    private function handleResponse(array $result)
    {
        return response()->json($result, $result['status']);
    }
}
