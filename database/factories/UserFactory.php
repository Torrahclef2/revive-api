<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Authentication
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'username' => fake()->unique()->userName(),
            'remember_token' => Str::random(10),

            // Profile
            'display_name' => fake()->name(),
            'avatar_url' => fake()->imageUrl(),
            'headline' => fake()->sentence(3),

            // Spiritual info
            'denomination' => fake()->randomElement(['Catholic', 'Protestant', 'Orthodox', 'Evangelical', 'Pentecostal', null]),
            'gender' => fake()->randomElement(['male', 'female', 'prefer_not_to_say']),
            'level' => fake()->randomElement(['seeker', 'rising_disciple', 'follower', 'faithful', 'leader']),

            // Location
            'location_city' => fake()->city(),
            'location_country' => fake()->countryCode(),

            // Engagement
            'xp_points' => fake()->numberBetween(0, 10000),
            'streak_count' => fake()->numberBetween(0, 365),
            'last_active_date' => fake()->dateTimeBetween('-30 days')->format('Y-m-d'),

            // Status
            'is_active' => true,
            'email_verified_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
