<?php

namespace App\Console\Commands;

use App\Models\Session;
use App\Models\User;
use App\Notifications\SessionReminderNotification;
use Illuminate\Console\Command;

class SendSessionReminders extends Command
{
    protected $signature   = 'sessions:send-reminders';
    protected $description = 'Send 15-minute reminders for sessions that are about to start.';

    public function handle(): int
    {
        // Find scheduled sessions starting between now+13min and now+16min
        // (3-minute window so a per-minute cron doesn't miss or double-send)
        $sessions = Session::where('status', 'waiting')
            ->where('reminder_sent', false)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [now()->addMinutes(13), now()->addMinutes(16)])
            ->with(['host:id,name', 'participants.user'])
            ->get();

        foreach ($sessions as $session) {
            // Collect all participant user IDs + group members of the host
            $participantIds = $session->participants
                ->pluck('user_id')
                ->filter()
                ->unique();

            $groupMemberIds = $session->host
                ? $session->host->groups()
                    ->with('users:id')
                    ->get()
                    ->flatMap(fn ($g) => $g->users->pluck('id'))
                    ->unique()
                : collect();

            $recipientIds = $participantIds->merge($groupMemberIds)->unique();

            User::whereIn('id', $recipientIds)
                ->get()
                ->each(fn ($user) => $user->notify(new SessionReminderNotification($session)));

            // Mark reminder as sent so we never dispatch it again for this session
            $session->update(['reminder_sent' => true]);

            $this->info("Reminders sent for session #{$session->id}");
        }

        return self::SUCCESS;
    }
}
