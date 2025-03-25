<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GutendexController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\GoogleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;

// Redirect old Google OAuth routes to new web routes
Route::get('/auth/google/redirect', function () {
    return redirect()->route('oauth.google.redirect');
});

Route::get('/auth/google/callback', function () {
    return redirect()->route('oauth.google.callback');
});

Route::get('/auth/google/verify/{token}', function ($token) {
    return redirect()->route('oauth.google.verify', ['token' => $token]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/refresh', [AuthController::class, 'refreshToken']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Favorites routes
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/{id}', [FavoriteController::class, 'store']);
        Route::delete('/{id}', [FavoriteController::class, 'destroy']);
        Route::get('/{id}/check', [FavoriteController::class, 'check']);
    });

    // User Activity routes
    Route::prefix('activities')->group(function () {
        Route::get('/', [UserActivityController::class, 'index']);
        Route::get('/{id}', [UserActivityController::class, 'show']);
        Route::delete('/clear', [UserActivityController::class, 'clear']);
    });

    // Notifications routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        // Permissions routes
        Route::prefix('permissions')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->middleware('requires.permission:permissions:manage');
            Route::post('/', [PermissionController::class, 'store'])->middleware('requires.permission:permissions:manage');
            Route::get('/{id}', [PermissionController::class, 'show'])->middleware('requires.permission:permissions:manage');
            Route::put('/{id}', [PermissionController::class, 'update'])->middleware('requires.permission:permissions:manage');
            Route::delete('/{id}', [PermissionController::class, 'destroy'])->middleware('requires.permission:permissions:manage');
            Route::post('/assign', [PermissionController::class, 'assignToRole'])->middleware('requires.permission:permissions:manage');
            Route::get('/roles/{roleId}', [PermissionController::class, 'getRolePermissions'])->middleware('requires.permission:permissions:manage');
        });
    });

    Route::prefix('gutendex')->group(function () {
        // Basic book routes accessible to all authenticated users
        Route::get('/books', [GutendexController::class, 'index'])->middleware('requires.permission:books:read');
        Route::get('/books/{id}', [GutendexController::class, 'show'])->middleware('requires.permission:books:read');
        
        // Book management routes requiring specific permissions
        Route::post('/books', [GutendexController::class, 'store'])->middleware('requires.permission:books:create');
        Route::delete('/books/{id}', [GutendexController::class, 'destroy'])->middleware('requires.permission:books:delete');
        Route::put('/books/{id}', [GutendexController::class, 'update'])->middleware('requires.permission:books:update');
        
        // Import routes requiring system:import permission
        Route::post('/bulk-import', [GutendexController::class, 'bulkImport'])->middleware('requires.permission:system:import');
        Route::post('/import-all-books', [GutendexController::class, 'importAllBooks'])->middleware('requires.permission:system:import');
        Route::post('/test-import', [GutendexController::class, 'testImport'])->middleware('requires.permission:system:import');
        Route::post('/direct-import', [GutendexController::class, 'directImport'])->middleware('requires.permission:system:import');
        
        // Category routes
        Route::get('/authors', [GutendexController::class, 'authors'])->middleware('requires.permission:books:read');
        Route::get('/authors/{id}/books', [GutendexController::class, 'booksByAuthor'])->middleware('requires.permission:books:read');
        Route::get('/categories', [GutendexController::class, 'categories'])->middleware('requires.permission:categories:read');
        Route::get('/categories/{id}/books', [GutendexController::class, 'booksByCategory'])->middleware('requires.permission:categories:read');
    });

    // Cart Routes - Có thể truy cập cả với khách và user đã đăng nhập
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{bookId}', [CartController::class, 'update']);
        Route::delete('/', [CartController::class, 'destroy']);
    });

    // Order Routes - Chỉ truy cập với user đã đăng nhập
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});

// Tạo named route cho import-all-books
Route::post('/gutendex/import-all-books', [App\Http\Controllers\GutendexController::class, 'importAllBooks'])
    ->middleware(['auth:api', 'requires.permission:system:import'])
    ->name('api.gutendex.import-all-books');

// Tạo route cho autocomplete suggestions
Route::get('/gutendex/suggestions', [App\Http\Controllers\AutocompleteController::class, 'suggestions'])
    ->middleware(['auth:api', 'requires.permission:books:read']);
Route::delete('/gutendex/suggestions/clear', [App\Http\Controllers\AutocompleteController::class, 'clearSuggestionCache'])
    ->middleware(['auth:api', 'requires.permission:system:manage']);

// Google OAuth Routes have been moved to web.php

// Publisher CRUD - Quản lý Nhà xuất bản
Route::get('/publishers', [PublisherController::class, 'index'])
    ->middleware(['auth:api', 'requires.permission:publishers:read'])
    ->name('api.publishers.index');

Route::get('/publishers/{id}', [PublisherController::class, 'show'])
    ->middleware(['auth:api', 'requires.permission:publishers:read'])
    ->name('api.publishers.show');

Route::get('/publishers/{id}/books', [PublisherController::class, 'books'])
    ->middleware(['auth:api', 'requires.permission:publishers:read'])
    ->name('api.publishers.books');

Route::post('/publishers', [PublisherController::class, 'store'])
    ->middleware(['auth:api', 'requires.permission:publishers:create'])
    ->name('api.publishers.store');

Route::put('/publishers/{id}', [PublisherController::class, 'update'])
    ->middleware(['auth:api', 'requires.permission:publishers:update'])
    ->name('api.publishers.update');

Route::delete('/publishers/{id}', [PublisherController::class, 'destroy'])
    ->middleware(['auth:api', 'requires.permission:publishers:delete'])
    ->name('api.publishers.destroy');
