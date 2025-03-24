<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisFavoriteService
{
    protected string $prefix = 'user:';
    protected string $suffix = ':favorites';

    public function add(string $userId, int $bookId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::sadd($key, $bookId);
    }

    public function remove(string $userId, int $bookId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::srem($key, $bookId);
    }

    public function check(string $userId, int $bookId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::sismember($key, $bookId);
    }

    public function getAll(string $userId): array
    {
        $key = $this->getKey($userId);
        return Redis::smembers($key);
    }

    public function count(string $userId): int
    {
        $key = $this->getKey($userId);
        return Redis::scard($key);
    }

    protected function getKey(string $userId): string
    {
        return $this->prefix . $userId . $this->suffix;
    }
} 