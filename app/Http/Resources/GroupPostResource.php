<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupPostResource extends JsonResource
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
            'content' => $this->content,
            'author' => new PublicProfileResource($this->whenLoaded('author')),
            'group_id' => $this->group_id,
            'reactions' => $this->when(
                $this->relationLoaded('reactions'),
                $this->formatReactions()
            ),
            'user_reaction' => $this->when(
                $request->user(),
                $this->getUserReaction($request->user()->id)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Format reactions by type with counts.
     */
    private function formatReactions(): array
    {
        $reactions = $this->reactions->groupBy('reaction')->map(function ($items) {
            return [
                'type' => $items[0]->reaction,
                'count' => $items->count(),
                'users' => $items->map(fn ($r) => $r->user_id)->take(3)->toArray(),
            ];
        });

        return $reactions->values()->toArray();
    }

    /**
     * Get current user's reaction to this post.
     */
    private function getUserReaction(string $userId): ?string
    {
        return $this->reactions
            ->where('user_id', $userId)
            ->pluck('reaction')
            ->first();
    }
}
