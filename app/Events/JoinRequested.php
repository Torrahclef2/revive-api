<?php

namespace App\Events;

use App\Models\PrayerSession;
use App\Models\SessionMember;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JoinRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public PrayerSession $session;
    public SessionMember $joinRequest;
    public User $requester;

    /**
     * Create a new event instance.
     */
    public function __construct(PrayerSession $session, SessionMember $joinRequest, User $requester)
    {
        $this->session = $session;
        $this->joinRequest = $joinRequest;
        $this->requester = $requester;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('session.' . $this->session->id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * Anonymizes user info if session is anonymous.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        // Check if session is anonymous (typically when visibility is 'anonymous')
        $isAnonymous = $this->session->visibility === 'anonymous';

        return [
            'session_id' => $this->session->id,
            'request_id' => $this->joinRequest->id,
            'requester' => [
                'id' => $this->requester->id,
                'username' => $isAnonymous ? 'Anonymous User' : $this->requester->username,
                'display_name' => $isAnonymous ? 'Anonymous' : $this->requester->display_name,
                'avatar_url' => $isAnonymous ? null : $this->requester->avatar_url,
            ],
            'message' => $isAnonymous 
                ? 'An anonymous user requested to join your session'
                : "{$this->requester->display_name} requested to join your session",
        ];
    }
}
