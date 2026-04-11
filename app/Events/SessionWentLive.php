<?php

namespace App\Events;

use App\Models\PrayerSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionWentLive implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public PrayerSession $session;
    public string $userId;

    /**
     * Create a new event instance.
     * 
     * This event is dispatched for each admitted member of the session.
     * The calling code should dispatch this event once per member.
     */
    public function __construct(PrayerSession $session, string $userId)
    {
        $this->session = $session;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
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
            'title' => $this->session->title,
            'agora_channel_name' => $this->session->agora_channel_name,
            'message' => 'Prayer session is now live',
        ];
    }
}
