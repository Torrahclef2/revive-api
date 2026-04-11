<?php

namespace App\Events;

use App\Models\PrayerSession;
use App\Models\SessionMember;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public PrayerSession $session;
    public SessionMember $member;

    /**
     * Create a new event instance.
     */
    public function __construct(PrayerSession $session, SessionMember $member)
    {
        $this->session = $session;
        $this->member = $member;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->member->user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'session_title' => $this->session->title,
            'message' => 'Your request to join the session has been rejected',
        ];
    }
}
