<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\RedisStore;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;

class RedisCacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Xóa cache liên quan khi thêm/cập nhật/xóa sách
        Book::created(function ($book) {
            $this->clearBooksCaches();
        });

        Book::updated(function ($book) {
            Cache::forget("books:detail:{$book->id}");
            Cache::forget("books:detail:{$book->gutendex_id}");
            $this->clearBooksCaches();
        });

        Book::deleted(function ($book) {
            Cache::forget("books:detail:{$book->id}");
            Cache::forget("books:detail:{$book->gutendex_id}");
            $this->clearBooksCaches();
        });

        // Xóa cache liên quan khi thêm/cập nhật/xóa tác giả
        Author::created(function ($author) {
            Cache::forget('authors:all');
            $this->clearBooksCaches();
        });

        Author::updated(function ($author) {
            Cache::forget('authors:all');
            $this->clearBooksCaches();
        });

        Author::deleted(function ($author) {
            Cache::forget('authors:all');
            $this->clearBooksCaches();
        });

        // Xóa cache liên quan khi thêm/cập nhật/xóa thể loại
        Category::created(function ($category) {
            Cache::forget('categories:all');
            $this->clearBooksCaches();
        });

        Category::updated(function ($category) {
            Cache::forget('categories:all');
            $this->clearBooksCaches();
        });

        Category::deleted(function ($category) {
            Cache::forget('categories:all');
            $this->clearBooksCaches();
        });
    }

    /**
     * Xóa tất cả các cache liên quan đến sách
     */
    protected function clearBooksCaches()
    {
        if (config('cache.default') === 'redis' && Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            try {
                $redis = Cache::getRedis();
                
                // Lấy tất cả các keys liên quan đến sách
                $bookListKeys = $redis->keys('books:list:*');
                foreach ($bookListKeys as $key) {
                    Cache::forget($key);
                }
                
                // Xóa tất cả cache suggestions
                $suggestionKeys = $redis->keys('suggestions:*');
                foreach ($suggestionKeys as $key) {
                    Cache::forget($key);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to clear Redis caches: ' . $e->getMessage());
            }
        } else {
            // Fallback cho non-Redis cache
            Cache::forget('categories:all');
            Cache::forget('authors:all');
            // Không thể làm pattern matching với non-Redis stores 
        }
    }
}
