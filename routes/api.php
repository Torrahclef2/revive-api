<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Revive App
|--------------------------------------------------------------------------
| All routes here are prefixed with /api automatically via bootstrap/app.php
*/

// ─── Public Authentication ─────────────────────────────────────────────────────
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);
Route::post('/auth/guest',    [AuthController::class, 'guestLogin']); // Creates a temporary anonymous user

// ─── Protected Routes (require valid Sanctum token) ───────────────────────────
Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckBanned::class])->group(function () {

    // Sessions
    Route::post('/sessions',            [SessionController::class, 'createSession']);
    Route::get('/sessions/live',        [SessionController::class, 'getLiveSessions']);
    Route::post('/sessions/{id}/join',  [SessionController::class, 'joinSession']);
    Route::post('/sessions/{id}/leave', [SessionController::class, 'leaveSession']);
    Route::post('/sessions/{id}/end',   [SessionController::class, 'endSession']);

    // Groups
    Route::post('/groups',          [GroupController::class, 'createGroup']);
    Route::get('/groups',           [GroupController::class, 'getUserGroups']);
    Route::post('/groups/{id}/join', [GroupController::class, 'joinGroup']);

    // User
    Route::get('/user/progress',  [UserController::class, 'getProgress']);
    Route::patch('/user/settings', [UserController::class, 'updateSettings']);

    // Messaging
    Route::post('/conversations',                             [MessageController::class, 'startConversation']);
    Route::get('/conversations',                              [MessageController::class, 'getConversations']);
    Route::get('/conversations/{id}/messages',               [MessageController::class, 'getMessages']);
    Route::post('/conversations/{id}/messages',              [MessageController::class, 'sendMessage']);
    // Notifications
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::post('/notifications/read-all',    [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read',   [NotificationController::class, 'markAsRead']);

    // Abuse reports
    Route::post('/reports', [ReportController::class, 'store']);
});
