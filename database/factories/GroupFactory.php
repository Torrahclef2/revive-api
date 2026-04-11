<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'name' => fake()->words(3, true),
            'purpose' => fake()->randomElement(['prayer', 'study']),
            'description' => fake()->paragraph(),
            'avatar_url' => fake()->imageUrl(),
            'is_active' => true,
        ];
    }

    /**
     * Set group as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set group purpose to prayer.
     */
    public function prayer(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => 'prayer',
        ]);
    }

    /**
     * Set group purpose to study.
     */
    public function study(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => 'study',
        ]);
    }

    /**
     * Set a specific creator.
     */
    public function withCreator(User|string $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'creator_id' => $userId,
        ]);
    }
}
