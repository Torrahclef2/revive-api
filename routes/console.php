<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CloseExpiredSessions;
use App\Jobs\ExpireSessionThreads;
use App\Jobs\ResetMissedStreaks;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Jobs
|--------------------------------------------------------------------------
|
| Define all scheduled jobs for the Revive application.
| These jobs handle automated tasks like session management, streak resets,
| and circle suggestion generation.
|
*/

// Close expired sessions (every minute)
Schedule::job(new CloseExpiredSessions)
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping()
    ->name('close-expired-sessions');

// Expire session thread posts (hourly)
Schedule::job(new ExpireSessionThreads)
    ->hourly()
    ->onOneServer()
    ->withoutOverlapping()
    ->name('expire-session-threads');

// Reset missed streaks (daily at 00:05 UTC)
Schedule::job(new ResetMissedStreaks)
    ->dailyAt('00:05')
    ->onOneServer()
    ->withoutOverlapping()
    ->name('reset-missed-streaks');

