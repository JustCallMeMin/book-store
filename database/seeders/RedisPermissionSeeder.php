<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Services\RedisPermissionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RedisPermissionSeeder extends Seeder
{
    protected RedisPermissionService $permissionService;
    
    public function __construct(RedisPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all roles
        $roles = Role::all();
        $this->command->info('Found ' . $roles->count() . ' roles');
        
        // Define permissions by role - using the exact case as stored in the database
        $permissionsByRole = [
            'Admin' => [
                // Books permissions
                'books:read', 'books:create', 'books:update', 'books:delete',
                // Users permissions
                'users:read', 'users:create', 'users:update', 'users:delete',
                // Categories permissions
                'categories:read', 'categories:create', 'categories:update', 'categories:delete',
                // Publishers permissions
                'publishers:read', 'publishers:create', 'publishers:update', 'publishers:delete',
                // Settings permissions
                'settings:read', 'settings:update',
                // System permissions
                'system:manage', 'system:logs', 'system:backup', 'system:import',
                'permissions:manage'
            ],
            'User' => [
                // Basic permissions
                'books:read', 'categories:read', 'publishers:read', 'profile:read', 'profile:update'
            ]
        ];
        
        foreach ($roles as $role) {
            $roleName = $role->name; // Use exact role name without strtolower
            
            if (!isset($permissionsByRole[$roleName])) {
                $this->command->warn("No permissions defined for role: {$role->name}");
                continue;
            }
            
            $permissions = $permissionsByRole[$roleName];
            
            // Clear existing permissions
            $this->permissionService->clearPermissions($role->id);
            $this->command->info("Cleared existing permissions for role: {$role->name}");
            
            // Set new permissions
            $this->permissionService->setPermissions($role->id, $permissions);
            $this->command->info("Added " . count($permissions) . " permissions to role: {$role->name}");
            
            // Verify permissions
            $newPermissions = $this->permissionService->getAllPermissions($role->id);
            $this->command->info("Verified " . count($newPermissions) . " permissions for {$role->name}");
        }
    }
}
