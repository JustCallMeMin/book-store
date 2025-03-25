<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RedisPermissionService
{
    protected string $prefix = 'role:';
    protected string $suffix = ':permissions';

    public function addPermission($roleId, string $permission): bool
    {
        $roleId = (string) $roleId;
        $key = $this->getKey($roleId);
        Log::debug('RedisPermissionService: Adding permission', ['role_id' => $roleId, 'permission' => $permission, 'key' => $key]);
        return (bool) Redis::hset($key, $permission, true);
    }

    public function removePermission($roleId, string $permission): bool
    {
        $roleId = (string) $roleId;
        $key = $this->getKey($roleId);
        Log::debug('RedisPermissionService: Removing permission', ['role_id' => $roleId, 'permission' => $permission, 'key' => $key]);
        return (bool) Redis::hdel($key, $permission);
    }

    public function hasPermission($roleId, string $permission): bool
    {
        $roleId = (string) $roleId;
        $key = $this->getKey($roleId);
        $exists = (bool) Redis::hexists($key, $permission);
        Log::debug('RedisPermissionService: Checking permission', [
            'role_id' => $roleId, 
            'permission' => $permission, 
            'key' => $key,
            'exists' => $exists
        ]);
        return $exists;
    }

    public function getAllPermissions($roleId): array
    {
        $roleId = (string) $roleId;
        $key = $this->getKey($roleId);
        $permissions = array_keys(Redis::hgetall($key));
        Log::debug('RedisPermissionService: Getting all permissions', [
            'role_id' => $roleId, 
            'key' => $key,
            'count' => count($permissions)
        ]);
        return $permissions;
    }

    public function setPermissions($roleId, array $permissions): bool
    {
        $roleId = (string) $roleId;
        $key = $this->getKey($roleId);
        Redis::del($key);
        
        $data = array_combine($permissions, array_fill(0, count($permissions), true));
        Log::debug('RedisPermissionService: Setting permissions', [
            'role_id' => $roleId, 
            'key' => $key,
            'count' => count($permissions)
        ]);
        return (bool) Redis::hmset($key, $data);
    }

    public function clearPermissions($roleId): bool
    {
        $roleId = (string) $roleId;
        $key = $this->getKey($roleId);
        Log::debug('RedisPermissionService: Clearing permissions', ['role_id' => $roleId, 'key' => $key]);
        return (bool) Redis::del($key);
    }

    protected function getKey(string $roleId): string
    {
        return $this->prefix . $roleId . $this->suffix;
    }
} 