<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\AuthService;
use App\Services\RedisActivityService;

class AuthController extends Controller
{
    /**
     * The authentication service instance.
     */
    protected AuthService $authService;
    
    /**
     * The activity logging service instance.
     */
    protected RedisActivityService $activityService;

    /**
     * Create a new AuthController instance.
     *
     * @param AuthService $authService
     * @param RedisActivityService $activityService
     */
    public function __construct(
        AuthService $authService,
        RedisActivityService $activityService
    ) {
        $this->authService = $authService;
        $this->activityService = $activityService;
    }

    /**
     * Register a new user account.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $result = $this->authService->register($request->all());
        
        $this->logActivityIfSuccessful(
            $result, 
            'register',
            'User registered successfully'
        );
        
        return $this->handleResponse($result);
    }

    /**
     * Login an existing user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);
        
        $result = $this->authService->login($request->all());
        
        $this->logActivityIfSuccessful(
            $result, 
            'login',
            'User logged in',
            ['method' => 'credentials']
        );
        
        return $this->handleResponse($result);
    }

    /**
     * Logout the current user.
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        $userId = auth()->id();
        $result = $this->authService->logout();
        
        if ($userId) {
            $this->logActivity(
                $userId,
                'logout',
                'User logged out'
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Get the authenticated user's profile.
     *
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $userId = auth()->id();
        $result = $this->authService->profile();
        
        if ($userId) {
            $this->logActivity(
                $userId,
                'view_profile',
                'User viewed their profile'
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:255',
        ]);
        
        $userId = auth()->id();
        $result = $this->authService->updateProfile($request->all());
        
        if ($result['status'] === 200 && $userId) {
            $this->logActivity(
                $userId,
                'update_profile',
                'User updated their profile',
                ['fields' => array_keys($request->except(['password', 'token']))]
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Change the authenticated user's password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $userId = auth()->id();
        $result = $this->authService->changePassword($request->all());
        
        if ($result['status'] === 200 && $userId) {
            $this->logActivity(
                $userId,
                'change_password',
                'User changed their password'
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Refresh the authentication token.
     *
     * @return JsonResponse
     */
    public function refreshToken(): JsonResponse
    {
        $userId = auth()->id();
        $result = $this->authService->refreshToken();
        
        if ($result['status'] === 200 && $userId) {
            $this->logActivity(
                $userId,
                'refresh_token',
                'User refreshed their token'
            );
        }
        
        return $this->handleResponse($result);
    }

    /**
     * Send password reset OTP to the user's email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email|exists:users,email'
        ]);
        
        return $this->handleResponse(
            $this->authService->sendPasswordResetOtp($request->email)
        );
    }

    /**
     * Reset the user's password using OTP.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        return $this->handleResponse(
            $this->authService->resetPassword($request->all())
        );
    }

    /**
     * Standardize the API response format.
     *
     * @param array $result
     * @return JsonResponse
     */
    private function handleResponse(array $result): JsonResponse
    {
        $status = $result['status'] ?? 500;
        return response()->json($result, $status);
    }
    
    /**
     * Log a user activity.
     *
     * @param int $userId
     * @param string $action
     * @param string $description
     * @param array $metadata
     * @return void
     */
    private function logActivity(
        int $userId, 
        string $action, 
        string $description, 
        array $metadata = []
    ): void {
        $this->activityService->log(
            $userId,
            $action,
            $description,
            $metadata,
            request()->ip(),
            request()->userAgent()
        );
    }
    
    /**
     * Log activity if the operation was successful.
     *
     * @param array $result
     * @param string $action
     * @param string $description
     * @param array $metadata
     * @return void
     */
    private function logActivityIfSuccessful(
        array $result,
        string $action,
        string $description,
        array $metadata = []
    ): void {
        $successStatuses = [200, 201];
        if (in_array($result['status'] ?? 0, $successStatuses) && isset($result['user'])) {
            $this->logActivity(
                $result['user']['id'],
                $action,
                $description,
                $metadata
            );
        }
    }
}
