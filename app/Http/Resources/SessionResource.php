<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
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
            'visibility' => $this->visibility,
            'status' => $this->status,
            'gender_preference' => $this->gender_preference,
            'location_city' => $this->location_city,
            'location_country' => $this->location_country,
            'max_members' => $this->max_members,
            'duration_minutes' => $this->duration_minutes,
            'scheduled_at' => $this->scheduled_at,
            'live_started_at' => $this->live_started_at,
            'live_ended_at' => $this->live_ended_at,
            'agora_channel_name' => $this->agora_channel_name,
            'host' => new PublicProfileResource($this->whenLoaded('host')),
            'admitted_members_count' => $this->members()->where('status', 'admitted')->count(),
            'available_spots' => $this->availableSpots(),
            'created_at' => $this->created_at,
        ];
    }
}
