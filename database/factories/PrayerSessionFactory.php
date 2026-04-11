<?php

namespace Database\Factories;

use App\Models\PrayerSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PrayerSession>
 */
class PrayerSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'host_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'purpose' => fake()->randomElement(['prayer', 'study']),
            'template' => fake()->randomElement(['intercessory_prayer', 'scripture_study', 'praise_worship', 'open']),
            'visibility' => fake()->randomElement(['circle_only', 'open', 'anonymous']),
            'status' => fake()->randomElement(['upcoming', 'admitting', 'live', 'ended']),
            'gender_preference' => fake()->randomElement(['any', 'male', 'female']),
            'location_city' => fake()->city(),
            'location_country' => fake()->countryCode(),
            'max_members' => fake()->numberBetween(5, 50),
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'live_started_at' => null,
            'live_ended_at' => null,
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'agora_channel_name' => 'prayer-session-' . Str::uuid(),
        ];
    }

    /**
     * Set status to upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'upcoming',
            'live_started_at' => null,
            'live_ended_at' => null,
        ]);
    }

    /**
     * Set status to live.
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'live',
            'live_started_at' => now()->subMinutes(15),
            'live_ended_at' => null,
        ]);
    }

    /**
     * Set status to ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ended',
            'live_started_at' => now()->subHours(1),
            'live_ended_at' => now(),
        ]);
    }

    /**
     * Set purpose to prayer.
     */
    public function prayer(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => 'prayer',
        ]);
    }

    /**
     * Set purpose to study.
     */
    public function study(): static
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => 'study',
        ]);
    }

    /**
     * Set a specific host.
     */
    public function withHost(User|string $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->state(fn (array $attributes) => [
            'host_id' => $userId,
        ]);
    }
}
