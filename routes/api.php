<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GutendexController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

    Route::prefix('gutendex')->group(function () {
        Route::get('/books', [GutendexController::class, 'index']);
        Route::get('/books/{id}', [GutendexController::class, 'show']);
        Route::post('/books', [GutendexController::class, 'store'])->middleware('auth:api');
        Route::delete('/books/{id}', [GutendexController::class, 'destroy'])->middleware('auth:api');
        Route::put('/books/{id}', [GutendexController::class, 'update'])->middleware('auth:api');
        Route::post('/bulk-import', [GutendexController::class, 'bulkImport'])->middleware('auth:api');
        Route::get('/authors', [GutendexController::class, 'authors']);
        Route::get('/authors/{id}/books', [GutendexController::class, 'booksByAuthor']);
        Route::get('/categories', [GutendexController::class, 'categories']);
        Route::get('/categories/{id}/books', [GutendexController::class, 'booksByCategory']);
        
        // Admin only route để import tất cả sách
        Route::post('/import-all-books', [GutendexController::class, 'importAllBooks'])->middleware('auth:api');
        
        // Test route to import a small batch of books
        Route::post('/test-import', [GutendexController::class, 'testImport'])->middleware('auth:api');
        
        // Direct test route that imports without queues
        Route::post('/direct-import', [GutendexController::class, 'directImport'])->middleware('auth:api');
    });
});

// Tạo named route cho import-all-books
Route::post('/gutendex/import-all-books', [App\Http\Controllers\GutendexController::class, 'importAllBooks'])
    ->middleware(['auth:api', \App\Http\Middleware\CheckRole::class.':admin'])
    ->name('api.gutendex.import-all-books');
