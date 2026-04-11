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

class CircleAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public Circle $circle;
    public User $acceptor;
    public User $requester;

    /**
     * Create a new event instance.
     */
    public function __construct(Circle $circle, User $acceptor, User $requester)
    {
        $this->circle = $circle;
        $this->acceptor = $acceptor;
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
            new PrivateChannel('circles.' . $this->requester->id),
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
            'acceptor' => [
                'id' => $this->acceptor->id,
                'username' => $this->acceptor->username,
                'display_name' => $this->acceptor->display_name,
                'avatar_url' => $this->acceptor->avatar_url,
            ],
            'message' => "{$this->acceptor->display_name} accepted your circle request",
        ];
    }
}
