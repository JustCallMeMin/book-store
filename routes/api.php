<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GutendexController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/verify-remember', [AuthController::class, 'verifyRememberToken']);
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
        Route::post('/books', [GutendexController::class, 'store']);
        Route::delete('/books/{id}', [GutendexController::class, 'destroy']);
        Route::get('/authors', [GutendexController::class, 'authors']);
        Route::get('/authors/{id}/books', [GutendexController::class, 'booksByAuthor']);
    });
});
