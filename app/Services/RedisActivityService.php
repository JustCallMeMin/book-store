<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RedisActivityService
{
    protected string $prefix = 'user:';
    protected string $suffix = ':activities';
    protected int $maxActivities = 1000; // Giới hạn số lượng hoạt động lưu trữ

    public function log(
        string $userId,
        string $activityType,
        string $description,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): bool {
        $key = $this->getKey($userId);
        
        $activity = [
            'id' => (string) Str::uuid(),
            'type' => $activityType,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => Carbon::now()->toIso8601String()
        ];

        // Thêm hoạt động mới vào đầu danh sách
        Redis::lpush($key, json_encode($activity));
        
        // Giữ danh sách trong giới hạn
        Redis::ltrim($key, 0, $this->maxActivities - 1);

        return true;
    }

    public function getRecent(string $userId, int $limit = 10, int $offset = 0): array
    {
        $key = $this->getKey($userId);
        $activities = Redis::lrange($key, $offset, $offset + $limit - 1);
        
        return array_map(function ($activity) {
            return json_decode($activity, true);
        }, $activities);
    }

    public function clear(string $userId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::del($key);
    }

    public function count(string $userId): int
    {
        $key = $this->getKey($userId);
        return Redis::llen($key);
    }

    protected function getKey(string $userId): string
    {
        return $this->prefix . $userId . $this->suffix;
    }
} 