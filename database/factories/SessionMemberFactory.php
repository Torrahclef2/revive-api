<?php

namespace Database\Factories;

use App\Models\PrayerSession;
use App\Models\SessionMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionMember>
 */
class SessionMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => PrayerSession::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['requested', 'admitted', 'rejected', 'kicked']),
            'joined_at' => fake()->randomElement([null, now()->subMinutes(30)]),
            'left_at' => null,
        ];
    }

    /**
     * Set status to requested (pending).
     */
    public function requested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'requested',
            'joined_at' => null,
            'left_at' => null,
        ]);
    }

    /**
     * Set status to admitted.
     */
    public function admitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'admitted',
            'joined_at' => now()->subMinutes(fake()->numberBetween(5, 120)),
            'left_at' => null,
        ]);
    }

    /**
     * Set status to rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'joined_at' => null,
            'left_at' => null,
        ]);
    }

    /**
     * Set status to kicked.
     */
    public function kicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'kicked',
            'joined_at' => now()->subMinutes(fake()->numberBetween(30, 120)),
            'left_at' => now(),
        ]);
    }

    /**
     * Set a specific session.
     */
    public function forSession(PrayerSession|string $session): static
    {
        $sessionId = $session instanceof PrayerSession ? $session->id : $session;

        return $this->state(fn (array $attributes) => [
            'session_id' => $sessionId,
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
