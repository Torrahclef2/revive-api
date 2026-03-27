<?php

namespace App\Notifications;

use App\Models\Session;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to group members when a host creates a scheduled session.
 */
class SessionScheduledNotification extends Notification
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
            'type'         => 'session_scheduled',
            'session_id'   => $this->session->id,
            'session_type' => $this->session->type,
            'description'  => $this->session->description,
            'host_name'    => $this->session->host->name ?? 'Someone',
            'scheduled_at' => $this->session->scheduled_at?->toISOString(),
            'duration'     => $this->session->duration,
            'message'      => ($this->session->host->name ?? 'Someone')
                . ' scheduled a ' . $this->session->type . ' session for '
                . $this->session->scheduled_at?->format('M j, Y \a\t g:i A T'),
        ];
    }
}
