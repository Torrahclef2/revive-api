<?php

namespace App\Jobs;

use App\Events\SessionEnded;
use App\Models\PrayerSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CloseExpiredSessions implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * Finds all sessions that have exceeded their scheduled_at + duration_minutes
     * and marks them as ended, fires events, and awards XP to admitted members.
     */
    public function handle(): void
    {
        Log::info('CloseExpiredSessions job started');

        $expiredSessions = PrayerSession::where('status', '!=', 'ended')
            ->get()
            ->filter(function (PrayerSession $session) {
                // Check if session has passed its end time
                if (!$session->scheduled_at || !$session->duration_minutes) {
                    return false;
                }

                $endTime = $session->scheduled_at->addMinutes($session->duration_minutes);
                return now()->isAfter($endTime);
            });

        foreach ($expiredSessions as $session) {
            try {
                // Update session status
                $session->update([
                    'status' => 'ended',
                    'live_ended_at' => now(),
                ]);

                Log::info('Session ended', [
                    'session_id' => $session->id,
                    'title' => $session->title,
                ]);

                // Fire SessionEnded event for all admitted members
                event(new SessionEnded($session));

                // Trigger post-session circle suggestions generation
                GenerateCircleSuggestions::dispatch($session);

                // Award XP to all admitted members
                AwardSessionXP::dispatch($session);
            } catch (\Exception $e) {
                Log::error('Error closing expired session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('CloseExpiredSessions job completed', [
            'sessions_closed' => $expiredSessions->count(),
        ]);
    }
}
