<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'Revive API is running']);
});

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes (public)
    Route::post('/auth/register', 'Auth\RegisterController@register')->name('register');
    Route::post('/auth/login', 'Auth\LoginController@login')->name('login');
    Route::post('/auth/forgot-password', 'Auth\ForgotPasswordController@store')->name('password.email');
    Route::post('/auth/reset-password', 'Auth\ResetPasswordController@store')->name('password.store');
});

// Protected routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', 'Auth\LogoutController@logout')->name('logout');
    Route::get('/me', 'Auth\MeController@show')->name('me');
    Route::put('/me', 'Auth\MeController@update')->name('me.update');

    // Add protected routes here
});
