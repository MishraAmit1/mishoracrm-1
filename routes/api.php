<?php 
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api;
use App\Http\Controllers\Api\Auth\AuthController;

Route::prefix('v1')->group(function () {
 
    // Register + Login (no auth needed)
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
 
    // Protected API routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout',           [AuthController::class, 'logout']);
        Route::post('/logout-all',       [AuthController::class, 'logoutAll']);
        Route::get('/me',                [AuthController::class, 'me']);
        Route::put('/me',                [AuthController::class, 'updateProfile']);
        Route::put('/me/password',       [AuthController::class, 'changePassword']);
        Route::post('/me/avatar',        [AuthController::class, 'uploadAvatar']);
    });
});