<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'purpose' => $this->purpose,
            'template' => $this->template,
            'status' => $this->status,
            'host' => $this->when($this->visibility !== 'anonymous', [
                'id' => $this->host->id,
                'username' => $this->host->username,
                'display_name' => $this->host->display_name,
            ]),
            'location_city' => $this->location_city,
            'location_country' => $this->location_country,
            'max_members' => $this->max_members,
            'scheduled_at' => $this->scheduled_at,
            'live_started_at' => $this->live_started_at,
            'live_ended_at' => $this->live_ended_at,
            'duration_minutes' => $this->duration_minutes,
            'members_count' => $this->members()->where('status', 'admitted')->count(),
            'user_role' => $this->when(auth()->check(), $this->getUserRole(auth()->user())),
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Get user's role in this session (hosted/joined/etc).
     */
    private function getUserRole($user)
    {
        if ($this->isHostedBy($user)) {
            return 'host';
        }

        $membership = $this->members()->where('user_id', $user->id)->first();
        return $membership ? $membership->status : null;
    }
}
