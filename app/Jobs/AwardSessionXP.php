<?php

namespace App\Jobs;

use App\Models\PrayerSession;
use App\Services\StreakService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AwardSessionXP implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private PrayerSession $session
    ) {}

    /**
     * Execute the job.
     *
     * Awards XP to the host and all admitted members based on session duration.
     */
    public function handle(StreakService $streakService): void
    {
        Log::info('AwardSessionXP job started', [
            'session_id' => $this->session->id,
        ]);

        // Award XP to host
        try {
            $host = $this->session->host()->first();
            if ($host) {
                $result = $streakService->awardSessionXP($host, $this->session, 'host');
                Log::info('XP awarded to host', [
                    'user_id' => $host->id,
                    'xp_earned' => $result['xp_earned'],
                    'new_level' => $result['new_level'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error awarding XP to host', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Award XP to admitted members
        $admittedMembers = $this->session->members()
            ->where('status', 'admitted')
            ->get();

        foreach ($admittedMembers as $member) {
            try {
                $user = $member->user()->first();
                if ($user) {
                    $result = $streakService->awardSessionXP($user, $this->session, 'member');
                    Log::info('XP awarded to member', [
                        'user_id' => $user->id,
                        'xp_earned' => $result['xp_earned'],
                        'new_level' => $result['new_level'],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error awarding XP to member', [
                    'session_id' => $this->session->id,
                    'member_id' => $member->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('AwardSessionXP job completed', [
            'session_id' => $this->session->id,
            'members_count' => $admittedMembers->count() + 1,
        ]);
    }
}
