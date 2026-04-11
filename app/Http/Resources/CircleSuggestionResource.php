<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CircleSuggestionResource extends JsonResource
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
            'session_id' => $this->session_id,
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'status' => $this->status,
            'from_user' => new PublicProfileResource($this->whenLoaded('fromUser')),
            'to_user' => new PublicProfileResource($this->whenLoaded('toUser')),
            'session' => [
                'id' => $this->session->id,
                'title' => $this->session->title,
                'purpose' => $this->session->purpose,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
