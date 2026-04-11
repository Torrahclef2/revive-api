<?php

namespace App\Services;

use App\Models\StreakLog;
use App\Models\User;
use App\Models\PrayerSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class StreakService
{
    /**
     * XP awards for different activities.
     */
    private const XP_AWARDS = [
        'host' => 30,
        'member' => 20,
        'duration_bonus' => 10, // For sessions > 30 minutes
    ];

    /**
     * Level thresholds based on total XP.
     */
    private const LEVEL_THRESHOLDS = [
        'seeker' => 0,
        'rising_disciple' => 500,
        'follower' => 1500,
        'faithful' => 3000,
        'leader' => 6000,
    ];

    /**
     * Award XP for a prayer session.
     *
     * Calculates XP based on role and session duration, logs the activity,
     * updates streak, and checks for level advancement.
     *
     * @param User $user
     * @param PrayerSession $session
     * @param string $role 'host' or 'member'
     * @return array ['xp_earned' => int, 'total_xp' => int, 'level_change' => bool, 'new_level' => ?string]
     */
    public function awardSessionXP(User $user, PrayerSession $session, string $role): array
    {
        // Validate role
        if (!in_array(strtolower($role), ['host', 'member'])) {
            throw new \InvalidArgumentException("Role must be 'host' or 'member'");
        }

        $role = strtolower($role);

        // Calculate base XP
        $xpEarned = self::XP_AWARDS[$role];

        // Add duration bonus if session lasted > 30 minutes
        $durationMinutes = $session->duration_minutes ?? 0;
        if ($durationMinutes > 30) {
            $xpEarned += self::XP_AWARDS['duration_bonus'];
        }

        // Check if already logged today for this session
        $today = Carbon::now()->toDateString();
        $existingLog = StreakLog::where('user_id', $user->id)
            ->where('session_id', $session->id)
            ->whereDate('activity_date', $today)
            ->exists();

        if ($existingLog) {
            Log::info('XP already awarded for this session today', [
                'user_id' => $user->id,
                'session_id' => $session->id,
            ]);
            
            return [
                'xp_earned' => 0,
                'total_xp' => $user->xp_points,
                'level_change' => false,
                'new_level' => $user->level,
            ];
        }

        // Create streak log
        StreakLog::create([
            'user_id' => $user->id,
            'session_id' => $session->id,
            'activity_date' => $today,
            'activity_type' => $role,
            'xp_earned' => $xpEarned,
        ]);

        // Update user XP
        $oldXP = $user->xp_points;
        $user->xp_points += $xpEarned;

        // Check and update streak
        $this->checkAndUpdateStreak($user);

        // Check for level advancement
        $oldLevel = $user->level;
        $newLevel = $this->getLevelForXP($user->xp_points);
        $levelChanged = $oldLevel !== $newLevel;

        if ($levelChanged) {
            $user->level = $newLevel;
            Log::info('User level advanced', [
                'user_id' => $user->id,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'xp_points' => $user->xp_points,
            ]);
        }

        // Save user
        $user->save();

        return [
            'xp_earned' => $xpEarned,
            'total_xp' => $user->xp_points,
            'level_change' => $levelChanged,
            'new_level' => $newLevel,
        ];
    }

    /**
     * Check and update user's streak.
     *
     * - If last_active_date = yesterday: increment streak
     * - If last_active_date = today: no change
     * - If last_active_date < yesterday: reset streak to 1
     * Updates last_active_date to today.
     *
     * @param User $user
     * @return void
     */
    public function checkAndUpdateStreak(User $user): void
    {
        $today = Carbon::now()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();
        $lastActive = $user->last_active_date?->toDateString();

        if ($lastActive === $today) {
            // Already active today - no change
            return;
        }

        if ($lastActive === $yesterday) {
            // Active yesterday - increment streak
            $user->streak_count = ($user->streak_count ?? 0) + 1;
        } else {
            // Inactive for more than a day - reset streak to 1
            $user->streak_count = 1;
        }

        $user->last_active_date = Carbon::now();
        $user->save();

        Log::info('User streak updated', [
            'user_id' => $user->id,
            'streak_count' => $user->streak_count,
            'last_active_date' => $user->last_active_date,
        ]);
    }

    /**
     * Reset missed streaks for users inactive for more than 1 day.
     *
     * This should be called by a scheduler daily at midnight.
     * Resets streak_count to 0 for users where last_active_date is before yesterday.
     *
     * @return int Number of users affected
     */
    public static function resetMissedStreaks(): int
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $affectedCount = User::where('last_active_date', '<', $yesterday)
            ->where('streak_count', '>', 0)
            ->update(['streak_count' => 0]);

        if ($affectedCount > 0) {
            Log::info('Missed streaks reset', [
                'count' => $affectedCount,
                'reset_date' => Carbon::now(),
            ]);
        }

        return $affectedCount;
    }

    /**
     * Get the level name for a given XP total.
     *
     * @param int $xp Total XP points
     * @return string Level name
     */
    public function getLevelForXP(int $xp): string
    {
        // Iterate through thresholds in reverse order to find highest matching level
        $level = 'seeker'; // Default

        foreach (array_reverse(self::LEVEL_THRESHOLDS, preserve_keys: true) as $levelName => $threshold) {
            if ($xp >= $threshold) {
                $level = $levelName;
                break;
            }
        }

        return $level;
    }

    /**
     * Get all level thresholds.
     *
     * @return array
     */
    public static function getLevelThresholds(): array
    {
        return self::LEVEL_THRESHOLDS;
    }

    /**
     * Get all XP awards.
     *
     * @return array
     */
    public static function getXPAwards(): array
    {
        return self::XP_AWARDS;
    }
}
