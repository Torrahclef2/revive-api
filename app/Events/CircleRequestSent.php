<?php

namespace App\Events;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CircleRequestSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public Circle $circle;
    public User $recipient;
    public User $sender;

    /**
     * Create a new event instance.
     */
    public function __construct(Circle $circle, User $recipient, User $sender)
    {
        $this->circle = $circle;
        $this->recipient = $recipient;
        $this->sender = $sender;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('circles.' . $this->recipient->id),
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
            'circle_id' => $this->circle->id,
            'sender' => [
                'id' => $this->sender->id,
                'username' => $this->sender->username,
                'display_name' => $this->sender->display_name,
                'avatar_url' => $this->sender->avatar_url,
            ],
            'message' => "{$this->sender->display_name} sent you a circle request",
        ];
    }
}
