<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
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
            'description' => $this->description,
            'purpose' => $this->purpose,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,
            'creator' => new PublicProfileResource($this->whenLoaded('creator')),
            'members_count' => $this->members()->count(),
            'members' => UserResource::collection($this->whenLoaded('members', function () {
                return $this->members()->with('user')->get()->pluck('user');
            })),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
