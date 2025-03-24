<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Carbon;

class RedisNotificationService
{
    protected string $prefix = 'user:';
    protected string $notificationSuffix = ':notifications';
    protected string $readSuffix = ':read_notifications';
    protected int $maxNotifications = 1000; // Giới hạn số lượng thông báo lưu trữ

    public function send(
        string $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): bool {
        $key = $this->getKey($userId);
        
        $notification = [
            'id' => uniqid('notif_', true),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'created_at' => Carbon::now()->toIso8601String()
        ];

        // Thêm thông báo mới với timestamp là score
        Redis::zadd($key, Carbon::now()->timestamp, json_encode($notification));
        
        // Giữ số lượng thông báo trong giới hạn
        $count = Redis::zcard($key);
        if ($count > $this->maxNotifications) {
            Redis::zremrangebyrank($key, 0, $count - $this->maxNotifications - 1);
        }

        return true;
    }

    public function markAsRead(string $userId, string $notificationId): bool
    {
        $readKey = $this->getReadKey($userId);
        return (bool) Redis::sadd($readKey, $notificationId);
    }

    public function markAllAsRead(string $userId): bool
    {
        $key = $this->getKey($userId);
        $readKey = $this->getReadKey($userId);
        
        $notifications = Redis::zrange($key, 0, -1);
        foreach ($notifications as $notification) {
            $data = json_decode($notification, true);
            Redis::sadd($readKey, $data['id']);
        }

        return true;
    }

    public function getAll(string $userId, int $limit = 10, int $offset = 0): array
    {
        $key = $this->getKey($userId);
        $readKey = $this->getReadKey($userId);
        
        $notifications = Redis::zrevrange($key, $offset, $offset + $limit - 1, 'WITHSCORES');
        $readNotifications = Redis::smembers($readKey);
        
        $result = [];
        foreach ($notifications as $notification => $score) {
            $data = json_decode($notification, true);
            $data['read'] = in_array($data['id'], $readNotifications);
            $result[] = $data;
        }

        return $result;
    }

    public function getUnread(string $userId): array
    {
        $key = $this->getKey($userId);
        $readKey = $this->getReadKey($userId);
        
        $notifications = Redis::zrange($key, 0, -1);
        $readNotifications = Redis::smembers($readKey);
        
        $result = [];
        foreach ($notifications as $notification) {
            $data = json_decode($notification, true);
            if (!in_array($data['id'], $readNotifications)) {
                $result[] = $data;
            }
        }

        return $result;
    }

    public function delete(string $userId, string $notificationId): bool
    {
        $key = $this->getKey($userId);
        $readKey = $this->getReadKey($userId);
        
        $notifications = Redis::zrange($key, 0, -1);
        foreach ($notifications as $notification) {
            $data = json_decode($notification, true);
            if ($data['id'] === $notificationId) {
                Redis::zrem($key, $notification);
                Redis::srem($readKey, $notificationId);
                return true;
            }
        }

        return false;
    }

    public function clear(string $userId): bool
    {
        $key = $this->getKey($userId);
        $readKey = $this->getReadKey($userId);
        
        Redis::del($key);
        Redis::del($readKey);

        return true;
    }

    protected function getKey(string $userId): string
    {
        return $this->prefix . $userId . $this->notificationSuffix;
    }

    protected function getReadKey(string $userId): string
    {
        return $this->prefix . $userId . $this->readSuffix;
    }
} 