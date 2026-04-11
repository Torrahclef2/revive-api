<?php

namespace Database\Factories;

use App\Models\PrayerSession;
use App\Models\SessionThreadPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionThreadPost>
 */
class SessionThreadPostFactory extends Factory
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
            'author_id' => User::factory(),
            'content' => fake()->paragraphs(2, true),
            'expires_at' => now()->addHours(48),
        ];
    }

    /**
     * Set content to short message.
     */
    public function short(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->sentence(),
        ]);
    }

    /**
     * Set content to long message.
     */
    public function long(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->paragraphs(5, true),
        ]);
    }

    /**
     * Set expiration to past (already expired).
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(1),
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
     * Set a specific author.
     */
    public function byAuthor(User|string $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'author_id' => $userId,
        ]);
    }
}
