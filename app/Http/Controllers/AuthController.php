<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Đăng ký tài khoản mới.
     */
    public function register(Request $request)
    {
        $result = $this->authService->register($request->all());
        return response()->json($result, $result['status']);
    }

    /**
     * Đăng nhập tài khoản đã có.
     */
    public function login(Request $request)
    {
        $result = $this->authService->login($request->all());
        return response()->json($result, $result['status']);
    }

    /**
     * Gửi email quên mật khẩu với OTP.
     */
    public function forgotPassword(Request $request)
    {
        $result = $this->authService->sendPasswordResetOtp($request->email);
        return response()->json($result, $result['status']);
    }

    /**
     * Reset mật khẩu bằng OTP.
     */
    public function resetPassword(Request $request)
    {
        $result = $this->authService->resetPassword($request->all());
        return response()->json($result, $result['status']);
    }
}
