<?php
// This is a script to add permissions directly to Redis
// Usage: php scripts/add_permissions.php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Access the RedisPermissionService
$permissionService = app()->make(\App\Services\RedisPermissionService::class);

// Get roles
$roles = \App\Models\Role::all();
echo "Found " . $roles->count() . " roles\n";

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

// Process each role
foreach ($roles as $role) {
    $roleName = strtolower($role->name);
    
    if (!isset($permissionsByRole[$roleName])) {
        echo "No permissions defined for role: {$role->name}\n";
        continue;
    }
    
    $permissions = $permissionsByRole[$roleName];
    
    // Clear existing permissions
    $permissionService->clearPermissions($role->id);
    echo "Cleared existing permissions for role: {$role->name}\n";
    
    // Set new permissions
    $permissionService->setPermissions($role->id, $permissions);
    echo "Added " . count($permissions) . " permissions to role: {$role->name}\n";
    
    // Verify permissions
    $newPermissions = $permissionService->getAllPermissions($role->id);
    echo "Verified permissions for {$role->name}: " . implode(', ', $newPermissions) . "\n";
}

echo "\nPermissions set successfully!\n"; 