<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * 
     * This resource only exposes public-safe information about a user.
     * Sensitive data like email, gender, and location details are excluded.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'headline' => $this->headline,
            'denomination' => $this->denomination,
            'level' => $this->level,
            'xp_points' => $this->xp_points,
            'streak_count' => $this->streak_count,
            'circles_count' => $this->activeCircles()->count(),
            'created_at' => $this->created_at,
        ];
    }
}
