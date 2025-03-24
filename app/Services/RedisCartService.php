<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\Models\Book;
use Illuminate\Support\Collection;

class RedisCartService
{
    protected string $prefix = 'cart:';
    protected int $expireTime = 7 * 24 * 60 * 60; // 7 days

    public function addItem(string $userId, int $bookId, int $quantity): bool
    {
        $key = $this->getKey($userId);
        $result = Redis::hset($key, $bookId, $quantity);
        Redis::expire($key, $this->expireTime);
        return (bool) $result;
    }

    public function updateQuantity(string $userId, int $bookId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($userId, $bookId);
        }
        return $this->addItem($userId, $bookId, $quantity);
    }

    public function removeItem(string $userId, int $bookId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::hdel($key, $bookId);
    }

    public function getCart(string $userId): Collection
    {
        $key = $this->getKey($userId);
        $items = Redis::hgetall($key);
        
        if (empty($items)) {
            return collect();
        }

        $bookIds = array_keys($items);
        $books = Book::whereIn('id', $bookIds)->get();
        
        return $books->map(function ($book) use ($items) {
            return [
                'book' => $book,
                'quantity' => (int) $items[$book->id]
            ];
        });
    }

    public function getItemCount(string $userId): int
    {
        $key = $this->getKey($userId);
        return Redis::hlen($key);
    }

    public function getTotalQuantity(string $userId): int
    {
        $key = $this->getKey($userId);
        $items = Redis::hgetall($key);
        return array_sum($items);
    }

    public function clear(string $userId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::del($key);
    }

    public function exists(string $userId, int $bookId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::hexists($key, $bookId);
    }

    public function getQuantity(string $userId, int $bookId): int
    {
        $key = $this->getKey($userId);
        return (int) Redis::hget($key, $bookId) ?: 0;
    }

    protected function getKey(string $userId): string
    {
        return $this->prefix . $userId;
    }
} 