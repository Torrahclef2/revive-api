<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CircleController;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'Revive API is running']);
});

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes (public)
    Route::post('/auth/register', [AuthController::class, 'register'])->name('register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('login');

    // Profile routes (public)
    Route::get('/profiles/{username}', [ProfileController::class, 'show'])->name('profiles.show');
});

// Protected routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');

    // Profile routes (protected)
    Route::put('/me', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/me/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.uploadAvatar');
    Route::get('/me/history', [ProfileController::class, 'history'])->name('profile.history');

    // Circle routes (protected)
    Route::get('/circles', [CircleController::class, 'index'])->name('circles.index');
    Route::post('/circles/request/{user}', [CircleController::class, 'request'])->name('circles.request');
    Route::post('/circles/{circle}/respond', [CircleController::class, 'respond'])->name('circles.respond');
    Route::delete('/circles/{circle}', [CircleController::class, 'destroy'])->name('circles.destroy');
    Route::get('/circles/suggestions', [CircleController::class, 'suggestions'])->name('circles.suggestions');
    Route::post('/circles/suggestions/{suggestion}/respond', [CircleController::class, 'respondToSuggestion'])->name('circles.respondToSuggestion');

    // Add protected routes here
});
