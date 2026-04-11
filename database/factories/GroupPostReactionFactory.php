<?php

namespace Database\Factories;

use App\Models\GroupPost;
use App\Models\GroupPostReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupPostReaction>
 */
class GroupPostReactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => GroupPost::factory(),
            'user_id' => User::factory(),
            'reaction' => fake()->randomElement(['amen', 'heart', 'pray']),
        ];
    }

    /**
     * Set reaction to amen.
     */
    public function amen(): static
    {
        return $this->state(fn (array $attributes) => [
            'reaction' => 'amen',
        ]);
    }

    /**
     * Set reaction to heart.
     */
    public function heart(): static
    {
        return $this->state(fn (array $attributes) => [
            'reaction' => 'heart',
        ]);
    }

    /**
     * Set reaction to pray.
     */
    public function pray(): static
    {
        return $this->state(fn (array $attributes) => [
            'reaction' => 'pray',
        ]);
    }

    /**
     * Set a specific post.
     */
    public function forPost(GroupPost|string $post): static
    {
        $postId = $post instanceof GroupPost ? $post->id : $post;

        return $this->state(fn (array $attributes) => [
            'post_id' => $postId,
        ]);
    }

    /**
     * Set a specific user.
     */
    public function byUser(User|string $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
