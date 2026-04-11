<?php

namespace Database\Factories;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Circle>
 */
class CircleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get or create two different users
        $requester = User::inRandomOrder()->first() ?? User::factory()->create();
        $receiver = User::inRandomOrder()->where('id', '!=', $requester->id)->first() ?? User::factory()->create();

        return [
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => fake()->randomElement(['pending', 'accepted', 'rejected']),
        ];
    }

    /**
     * Set the circle status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Set the circle status to accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    /**
     * Set the circle status to rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Set a specific requester.
     */
    public function withRequester(User|string $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'requester_id' => $userId,
        ]);
    }

    /**
     * Set a specific receiver.
     */
    public function withReceiver(User|string $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'receiver_id' => $userId,
        ]);
    }
}
