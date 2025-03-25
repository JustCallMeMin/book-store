<?php

namespace App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\RequiresPermission;
use App\Http\Middleware\MergeCartItems;
use App\Http\Middleware\RequireClaim;

class MiddlewareServiceProvider extends ServiceProvider
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
        $router = $this->app->make(Router::class);
        
        // Register middleware aliases
        $router->aliasMiddleware('requires.permission', RequiresPermission::class);
        $router->aliasMiddleware('requires', RequireClaim::class);
        $router->aliasMiddleware('merge.cart', MergeCartItems::class);
        
        // Register API middleware group
        $router->middlewareGroup('api', [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Thêm middleware MergeCartItems vào trong web routes
        $router->pushMiddlewareToGroup('web', MergeCartItems::class);
    }
}
