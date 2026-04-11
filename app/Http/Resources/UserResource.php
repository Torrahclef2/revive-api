<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'headline' => $this->headline,
            'denomination' => $this->denomination,
            'gender' => $this->gender,
            'location_city' => $this->location_city,
            'location_country' => $this->location_country,
            'level' => $this->level,
            'xp_points' => $this->xp_points,
            'streak_count' => $this->streak_count,
            'last_active_date' => $this->last_active_date,
            'is_active' => $this->is_active,
            'circles_count' => $this->activeCircles()->count(),
            'sessions_count' => $this->prayerSessions()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
