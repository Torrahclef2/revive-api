<?php

namespace App\Jobs;

use App\Services\StreakService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ResetMissedStreaks implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * Resets streak counts for users who have been inactive for more than 1 day.
     * Run daily at 00:05 UTC.
     */
    public function handle(): void
    {
        Log::info('ResetMissedStreaks job started');

        try {
            $affectedCount = StreakService::resetMissedStreaks();

            Log::info('ResetMissedStreaks job completed', [
                'users_affected' => $affectedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ResetMissedStreaks job', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
