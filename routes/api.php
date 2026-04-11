<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'Revive API is running']);
});

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes (public)
    Route::post('/auth/register', [AuthController::class, 'register'])->name('register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
});

// Protected routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');

    // Add protected routes here
});
