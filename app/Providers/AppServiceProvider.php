<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AgoraService;
use App\Services\StreakService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register AgoraService as singleton
        $this->app->singleton(AgoraService::class, function ($app) {
            return new AgoraService();
        });

        // Register StreakService as singleton
        $this->app->singleton(StreakService::class, function ($app) {
            return new StreakService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
