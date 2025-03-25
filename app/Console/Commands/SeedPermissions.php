<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\RedisPermissionService;
use Illuminate\Console\Command;

class SeedPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-permissions {--force : Force reset existing permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed initial permissions for roles';
    
    /**
     * The permission service
     */
    protected RedisPermissionService $permissionService;
    
    /**
     * Create a new command instance.
     */
    public function __construct(RedisPermissionService $permissionService)
    {
        parent::__construct();
        $this->permissionService = $permissionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        // Get all roles
        $roles = Role::all();
        $this->info('Found ' . $roles->count() . ' roles');
        
        // Define permissions by role
        $permissionsByRole = [
            'admin' => [
                // Books permissions
                'books:read',
                'books:create',
                'books:update',
                'books:delete',
                // Users permissions
                'users:read',
                'users:create',
                'users:update',
                'users:delete',
                // Categories permissions
                'categories:read',
                'categories:create',
                'categories:update',
                'categories:delete',
                // Settings permissions
                'settings:read',
                'settings:update',
                // System permissions
                'system:manage',
                'system:logs',
                'system:backup',
                'system:import',
                'permissions:manage'
            ],
            'editor' => [
                // Books permissions
                'books:read',
                'books:create',
                'books:update',
                // Categories permissions
                'categories:read',
                'categories:create',
                'categories:update',
                // Limited user management
                'users:read'
            ],
            'user' => [
                // Basic permissions
                'books:read',
                'categories:read',
                'profile:read',
                'profile:update'
            ]
        ];
        
        foreach ($roles as $role) {
            $roleName = strtolower($role->name);
            
            // Skip if role has no defined permissions
            if (!isset($permissionsByRole[$roleName])) {
                $this->warn("No permissions defined for role: {$role->name}");
                continue;
            }
            
            $permissions = $permissionsByRole[$roleName];
            
            // Check if role already has permissions
            $existingPermissions = $this->permissionService->getAllPermissions($role->id);
            
            if (count($existingPermissions) > 0 && !$force) {
                $this->warn("Role {$role->name} already has " . count($existingPermissions) . " permissions. Use --force to reset.");
                continue;
            }
            
            // Clear existing permissions if forced
            if ($force) {
                $this->permissionService->clearPermissions($role->id);
                $this->info("Cleared existing permissions for role: {$role->name}");
            }
            
            // Set new permissions
            $this->permissionService->setPermissions($role->id, $permissions);
            $this->info("Added " . count($permissions) . " permissions to role: {$role->name}");
        }
        
        $this->newLine();
        $this->info('Permissions seeded successfully!');
        
        return Command::SUCCESS;
    }
}
