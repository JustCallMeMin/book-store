<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Redis;
use App\Services\RedisFavoriteService;
use App\Services\RedisActivityService;
use App\Services\RedisNotificationService;
use App\Services\RedisPermissionService;
use App\Services\RedisCartService;
use App\Services\RedisImportLogService;
use App\Services\CartService;

class RedisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedisFavoriteService::class, function ($app) {
            return new RedisFavoriteService();
        });

        $this->app->singleton(RedisActivityService::class, function ($app) {
            return new RedisActivityService();
        });

        $this->app->singleton(RedisNotificationService::class, function ($app) {
            return new RedisNotificationService();
        });

        $this->app->singleton(RedisPermissionService::class, function ($app) {
            return new RedisPermissionService();
        });

        $this->app->singleton(RedisCartService::class, function ($app) {
            return new RedisCartService();
        });
        
        // Bind the CartService interface to RedisCartService implementation
        $this->app->bind(CartService::class, RedisCartService::class);

        $this->app->singleton(RedisImportLogService::class, function ($app) {
            return new RedisImportLogService();
        });
    }

    public function boot(): void
    {
        // Prefix is configured in .env file
    }
} 