<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupPost>
 */
class GroupPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'author_id' => User::factory(),
            'content' => fake()->paragraphs(2, true),
        ];
    }

    /**
     * Set a specific group.
     */
    public function forGroup(Group|string $group): static
    {
        $groupId = $group instanceof Group ? $group->id : $group;

        return $this->state(fn (array $attributes) => [
            'group_id' => $groupId,
        ]);
    }

    /**
     * Set a specific author.
     */
    public function byAuthor(User|string $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'author_id' => $userId,
        ]);
    }

    /**
     * Set short content.
     */
    public function short(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->sentence(),
        ]);
    }

    /**
     * Set long content.
     */
    public function long(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->paragraphs(5, true),
        ]);
    }
}
