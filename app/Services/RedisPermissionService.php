<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisPermissionService
{
    protected string $prefix = 'role:';
    protected string $suffix = ':permissions';

    public function addPermission(string $roleId, string $permission): bool
    {
        $key = $this->getKey($roleId);
        return (bool) Redis::hset($key, $permission, true);
    }

    public function removePermission(string $roleId, string $permission): bool
    {
        $key = $this->getKey($roleId);
        return (bool) Redis::hdel($key, $permission);
    }

    public function hasPermission(string $roleId, string $permission): bool
    {
        $key = $this->getKey($roleId);
        return (bool) Redis::hexists($key, $permission);
    }

    public function getAllPermissions(string $roleId): array
    {
        $key = $this->getKey($roleId);
        return array_keys(Redis::hgetall($key));
    }

    public function setPermissions(string $roleId, array $permissions): bool
    {
        $key = $this->getKey($roleId);
        Redis::del($key);
        
        $data = array_combine($permissions, array_fill(0, count($permissions), true));
        return (bool) Redis::hmset($key, $data);
    }

    public function clearPermissions(string $roleId): bool
    {
        $key = $this->getKey($roleId);
        return (bool) Redis::del($key);
    }

    protected function getKey(string $roleId): string
    {
        return $this->prefix . $roleId . $this->suffix;
    }
} 