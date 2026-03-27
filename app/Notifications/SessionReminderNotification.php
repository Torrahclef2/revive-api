<?php

namespace App\Notifications;

use App\Models\Session;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to all session participants (and group members) ~15 minutes before
 * a scheduled session is about to start.
 */
class SessionReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Session $session) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'session_reminder',
            'session_id'   => $this->session->id,
            'session_type' => $this->session->type,
            'description'  => $this->session->description,
            'scheduled_at' => $this->session->scheduled_at?->toISOString(),
            'message'      => 'Reminder: a ' . $this->session->type
                . ' session starts in 15 minutes.',
        ];
    }
}
