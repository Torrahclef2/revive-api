<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupMember>
 */
class GroupMemberFactory extends Factory
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
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['admin', 'member']),
            'joined_at' => fake()->dateTimeBetween('-90 days'),
        ];
    }

    /**
     * Set role to admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Set role to member.
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'member',
        ]);
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
     * Set a specific user.
     */
    public function forUser(User|string $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
