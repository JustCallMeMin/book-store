<?php

namespace App\Http\Middleware;

use App\Services\RedisPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class RequiresPermission
{
    protected RedisPermissionService $permissionService;

    public function __construct(RedisPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Get user
        $user = $request->user();
        if (!$user) {
            Log::debug('RequiresPermission: No authenticated user');
            return $this->unauthorized();
        }
        
        // Get role IDs
        $roleIds = $user->roles()->pluck('id')->toArray();
        
        if (empty($roleIds)) {
            Log::debug('RequiresPermission: User has no roles', ['user_id' => $user->id]);
            return $this->unauthorized();
        }
        
        Log::debug('RequiresPermission: Checking permission', [
            'user_id' => $user->id,
            'roles' => $roleIds,
            'permission' => $permission
        ]);
        
        // Check if any role has the required permission
        foreach ($roleIds as $roleId) {
            $roleIdStr = (string) $roleId;
            Log::debug('RequiresPermission: Checking role', [
                'role_id' => $roleId,
                'role_id_type' => gettype($roleId)
            ]);
            
            if ($this->permissionService->hasPermission($roleIdStr, $permission)) {
                Log::debug('RequiresPermission: Permission granted', [
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                    'permission' => $permission
                ]);
                return $next($request);
            }
        }
        
        Log::debug('RequiresPermission: Permission denied', [
            'user_id' => $user->id,
            'roles' => $roleIds,
            'permission' => $permission
        ]);
        
        return $this->unauthorized();
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorized(): Response
    {
        return response()->json([
            'error' => 'Unauthorized. You do not have the required permission to access this resource.'
        ], 403);
    }
}
