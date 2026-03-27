<?php

namespace App\Services;

use App\Events\SessionEnded;
use App\Events\UserKicked;
use App\Events\UserMuted;
use App\Models\Report;
use App\Models\Session;
use App\Models\SessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SessionModerationService
{
    public function muteUser(Session $session, int $targetUserId, int $mutedBy): SessionParticipant
    {
        $participant = SessionParticipant::where('session_id', $session->id)
            ->where('user_id', $targetUserId)
            ->whereNull('left_at')
            ->firstOrFail();

        $participant->update([
            'is_muted' => true,
            'muted_at' => now(),
        ]);

        broadcast(new UserMuted(
            sessionId:    $session->id,
            targetUserId: $targetUserId,
            mutedBy:      $mutedBy,
            timestamp:    now()->toIso8601String(),
        ))->toOthers();

        return $participant->fresh();
    }

    public function kickUser(Session $session, int $targetUserId, int $kickedBy): SessionParticipant
    {
        $participant = SessionParticipant::where('session_id', $session->id)
            ->where('user_id', $targetUserId)
            ->whereNull('left_at')
            ->firstOrFail();

        $participant->update([
            'is_removed' => true,
            'removed_at' => now(),
            'left_at'    => now(),
        ]);

        broadcast(new UserKicked(
            sessionId:    $session->id,
            targetUserId: $targetUserId,
            kickedBy:     $kickedBy,
            timestamp:    now()->toIso8601String(),
        ))->toOthers();

        return $participant->fresh();
    }

    public function endSession(Session $session, int $endedBy): Session
    {
        $session->update([
            'status'   => 'ended',
            'ended_at' => now(),
        ]);

        // Mark all active participants as left
        SessionParticipant::where('session_id', $session->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        broadcast(new SessionEnded(
            sessionId: $session->id,
            endedBy:   $endedBy,
            timestamp: now()->toIso8601String(),
        ));

        return $session->fresh();
    }

    public function reportUser(
        Session $session,
        int $reporterUserId,
        int $reportedUserId,
        string $reason,
        ?string $description = null
    ): Report {
        $report = Report::create([
            'reporter_id'         => $reporterUserId,
            'reported_user_id'    => $reportedUserId,
            'reported_session_id' => $session->id,
            'reason'              => $reason,
            'description'         => $description,
            'status'              => 'pending',
        ]);

        $this->evaluateAutoModeration($session, $reportedUserId);

        return $report;
    }

    public function evaluateAutoModeration(Session $session, int $targetUserId): void
    {
        $reportCount = Report::where('reported_session_id', $session->id)
            ->where('reported_user_id', $targetUserId)
            ->count();

        if ($reportCount >= 3) {
            $participant = SessionParticipant::where('session_id', $session->id)
                ->where('user_id', $targetUserId)
                ->whereNull('left_at')
                ->first();

            if ($participant && !$participant->is_removed) {
                $participant->update([
                    'is_removed'  => true,
                    'removed_at'  => now(),
                    'left_at'     => now(),
                ]);

                // Mark the triggering reports as auto-moderated
                Report::where('reported_session_id', $session->id)
                    ->where('reported_user_id', $targetUserId)
                    ->update(['auto_moderated' => true]);

                broadcast(new UserKicked(
                    sessionId:    $session->id,
                    targetUserId: $targetUserId,
                    kickedBy:     0, // 0 = system
                    timestamp:    now()->toIso8601String(),
                ));

                // Reduce reputation
                User::where('id', $targetUserId)->decrement('reputation_score', 20);
            }
        } elseif ($reportCount >= 2) {
            $participant = SessionParticipant::where('session_id', $session->id)
                ->where('user_id', $targetUserId)
                ->whereNull('left_at')
                ->first();

            if ($participant && !$participant->is_muted) {
                $participant->update([
                    'is_muted' => true,
                    'muted_at' => now(),
                ]);

                Report::where('reported_session_id', $session->id)
                    ->where('reported_user_id', $targetUserId)
                    ->update(['auto_moderated' => true]);

                broadcast(new UserMuted(
                    sessionId:    $session->id,
                    targetUserId: $targetUserId,
                    mutedBy:      0, // 0 = system
                    timestamp:    now()->toIso8601String(),
                ));

                User::where('id', $targetUserId)->decrement('reputation_score', 10);
            }
        }
    }
}
