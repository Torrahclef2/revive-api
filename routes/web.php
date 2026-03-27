<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GroupAdminController;
use App\Http\Controllers\Admin\SessionAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.dashboard'));

// Admin auth (guest only)
Route::middleware('guest')->prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login']);
});

// Admin panel (auth + is_admin)
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Users
        Route::get('users',             [UserAdminController::class, 'index'])->name('users.index');
        Route::get('users/{user}',      [UserAdminController::class, 'show'])->name('users.show');
        Route::patch('users/{user}/verify', [UserAdminController::class, 'toggleVerified'])->name('users.toggleVerified');
        Route::patch('users/{user}/admin',  [UserAdminController::class, 'toggleAdmin'])->name('users.toggleAdmin');
        Route::delete('users/{user}',   [UserAdminController::class, 'destroy'])->name('users.destroy');

        // Sessions
        Route::get('sessions',           [SessionAdminController::class, 'index'])->name('sessions.index');
        Route::get('sessions/{session}', [SessionAdminController::class, 'show'])->name('sessions.show');
        Route::delete('sessions/{session}', [SessionAdminController::class, 'destroy'])->name('sessions.destroy');

        // Groups
        Route::get('groups',          [GroupAdminController::class, 'index'])->name('groups.index');
        Route::get('groups/{group}',  [GroupAdminController::class, 'show'])->name('groups.show');
        Route::delete('groups/{group}', [GroupAdminController::class, 'destroy'])->name('groups.destroy');

        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
    });
