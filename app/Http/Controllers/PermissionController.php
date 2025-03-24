<?php

namespace App\Http\Controllers;

use App\Services\RedisPermissionService;
use Illuminate\Http\Request;
use App\Models\Role;

class PermissionController extends Controller
{
    protected RedisPermissionService $permissionService;

    public function __construct(RedisPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $roles = Role::all();
        $result = [];

        foreach ($roles as $role) {
            $result[$role->id] = [
                'role' => $role,
                'permissions' => $this->permissionService->getAllPermissions($role->id)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|uuid|exists:roles,id',
            'permission' => 'required|string'
        ]);

        $this->permissionService->addPermission(
            $request->input('role_id'),
            $request->input('permission')
        );

        return response()->json([
            'success' => true,
            'message' => 'Permission added successfully'
        ]);
    }

    public function show($roleId)
    {
        $role = Role::findOrFail($roleId);
        $permissions = $this->permissionService->getAllPermissions($role->id);

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $role,
                'permissions' => $permissions
            ]
        ]);
    }

    public function update(Request $request, $roleId)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string'
        ]);

        $role = Role::findOrFail($roleId);
        $this->permissionService->setPermissions($role->id, $request->input('permissions'));

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully'
        ]);
    }

    public function destroy(Request $request, $roleId)
    {
        $request->validate([
            'permission' => 'required|string'
        ]);

        $role = Role::findOrFail($roleId);
        $this->permissionService->removePermission($role->id, $request->input('permission'));

        return response()->json([
            'success' => true,
            'message' => 'Permission removed successfully'
        ]);
    }

    public function assignToRole(Request $request)
    {
        $request->validate([
            'role_id' => 'required|uuid|exists:roles,id',
            'permissions' => 'required|array',
            'permissions.*' => 'string'
        ]);

        $this->permissionService->setPermissions(
            $request->input('role_id'),
            $request->input('permissions')
        );

        return response()->json([
            'success' => true,
            'message' => 'Permissions assigned successfully'
        ]);
    }

    public function getRolePermissions($roleId)
    {
        $role = Role::findOrFail($roleId);
        $permissions = $this->permissionService->getAllPermissions($role->id);

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }
}
