<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CircleController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SessionMemberController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\GroupPostController;
use App\Http\Controllers\Api\AgoraController;
use App\Http\Controllers\Api\SearchController;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'Revive API is running']);
});

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Authentication routes (public) - strict rate limiting to prevent brute force
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');

    // Public profile view - moderate rate limiting
    Route::get('/profile/{username}', [ProfileController::class, 'show'])->middleware('throttle:60,1')->name('profile.show');
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // ==================== Authentication Routes ====================
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('throttle:30,1')->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('throttle:60,1')->name('auth.me');

    // ==================== Profile Routes ====================
    Route::put('/profile', [ProfileController::class, 'update'])->middleware('throttle:10,1')->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->middleware('throttle:5,1')->name('profile.uploadAvatar');
    Route::get('/profile/history', [ProfileController::class, 'history'])->middleware('throttle:30,1')->name('profile.history');

    // ==================== Search Routes ====================
    Route::get('/search', [SearchController::class, 'search'])->middleware('throttle:30,1')->name('search');

    // ==================== Circle Routes ====================
    Route::get('/circles', [CircleController::class, 'index'])->middleware('throttle:60,1')->name('circles.index');
    Route::post('/circles/request/{user}', [CircleController::class, 'request'])->middleware('throttle:10,1')->name('circles.request');
    Route::put('/circles/{circle}/respond', [CircleController::class, 'respond'])->middleware('throttle:30,1')->name('circles.respond');
    Route::delete('/circles/{circle}', [CircleController::class, 'destroy'])->middleware('throttle:30,1')->name('circles.destroy');
    Route::get('/circles/suggestions', [CircleController::class, 'suggestions'])->middleware('throttle:60,1')->name('circles.suggestions');
    Route::put('/circles/suggestions/{suggestion}/respond', [CircleController::class, 'respondToSuggestion'])->middleware('throttle:30,1')->name('circles.suggestions.respond');

    // ==================== Group Routes ====================
    Route::get('/groups', [GroupController::class, 'index'])->middleware('throttle:60,1')->name('groups.index');
    Route::post('/groups', [GroupController::class, 'store'])->middleware('throttle:10,1')->name('groups.store');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->middleware('throttle:60,1')->name('groups.show');
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember'])->middleware('throttle:20,1')->name('groups.members.add');
    Route::delete('/groups/{group}/members/{member}', [GroupController::class, 'removeMember'])->middleware('throttle:20,1')->name('groups.members.remove');
    Route::get('/groups/{group}/posts', [GroupController::class, 'posts'])->middleware('throttle:60,1')->name('groups.posts.index');
    Route::post('/groups/{group}/posts', [GroupController::class, 'createPost'])->middleware('throttle:20,1')->name('groups.posts.store');
    Route::put('/groups/{group}/posts/{post}', [GroupPostController::class, 'update'])->middleware('throttle:20,1')->name('groups.posts.update');
    Route::delete('/groups/{group}/posts/{post}', [GroupPostController::class, 'destroy'])->middleware('throttle:20,1')->name('groups.posts.destroy');
    Route::post('/groups/{group}/posts/{post}/react', [GroupController::class, 'react'])->middleware('throttle:30,1')->name('groups.posts.react');

    // ==================== Prayer Session Routes ====================
    Route::get('/sessions/discovery', [SessionController::class, 'discovery'])->middleware('throttle:30,1')->name('sessions.discovery');
    Route::post('/sessions', [SessionController::class, 'store'])->middleware('throttle:10,1')->name('sessions.store');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])->middleware('throttle:60,1')->name('sessions.show');
    Route::post('/sessions/{session}/go-live', [SessionController::class, 'goLive'])->middleware('throttle:5,1')->name('sessions.goLive');
    Route::post('/sessions/{session}/end', [SessionController::class, 'end'])->middleware('throttle:5,1')->name('sessions.end');

    // ==================== Session Member Routes ====================
    Route::post('/sessions/{session}/request', [SessionMemberController::class, 'request'])->middleware('throttle:20,1')->name('sessions.members.request');
    Route::put('/sessions/{session}/admit/{member}', [SessionMemberController::class, 'admit'])->middleware('throttle:30,1')->name('sessions.members.admit');
    Route::put('/sessions/{session}/reject/{member}', [SessionMemberController::class, 'reject'])->middleware('throttle:30,1')->name('sessions.members.reject');
    Route::delete('/sessions/{session}/kick/{member}', [SessionMemberController::class, 'kick'])->middleware('throttle:30,1')->name('sessions.members.kick');
    Route::post('/sessions/{session}/leave', [SessionMemberController::class, 'leave'])->middleware('throttle:30,1')->name('sessions.members.leave');
    Route::post('/sessions/{session}/report', [SessionMemberController::class, 'report'])->middleware('throttle:5,1')->name('sessions.report');

    // ==================== Session Agora Token ====================
    Route::post('/sessions/{session}/agora-token', [AgoraController::class, 'generateToken'])->middleware('throttle:20,1')->name('sessions.agoraToken');

    // ==================== Session Thread Routes ====================
    // TODO: Implement SessionThreadController
    // Route::get('/sessions/{session}/thread', [SessionThreadController::class, 'index'])->name('sessions.thread.index');
    // Route::post('/sessions/{session}/thread', [SessionThreadController::class, 'store'])->name('sessions.thread.store');

});

