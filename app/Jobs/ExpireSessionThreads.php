<?php

namespace App\Jobs;

use App\Models\SessionThreadPost;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpireSessionThreads implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * Deletes all session thread posts that have expired (expires_at < now()).
     * Run hourly.
     */
    public function handle(): void
    {
        Log::info('ExpireSessionThreads job started');

        try {
            $deletedCount = SessionThreadPost::where('expires_at', '<', now())
                ->delete();

            Log::info('ExpireSessionThreads job completed', [
                'posts_deleted' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ExpireSessionThreads job', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
