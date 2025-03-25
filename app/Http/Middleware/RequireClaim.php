<?php

namespace App\Http\Middleware;

use App\Services\RedisPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireClaim
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
            return $this->unauthorized();
        }
        
        // Get role IDs
        $roleIds = $user->roles()->pluck('id')->toArray();
        
        // Check if any role has the required permission
        foreach ($roleIds as $roleId) {
            if ($this->permissionService->hasPermission($roleId, $permission)) {
                return $next($request);
            }
        }
        
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
