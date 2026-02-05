<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserIntegration>
 */
class UserIntegrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'discord',
            'provider_id' => (string) $this->faker->unique()->numberBetween(100000000000000000, 999999999999999999),
            'provider_name' => $this->faker->userName(),
            'provider_email' => $this->faker->safeEmail(),
            'provider_avatar' => null,
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'expires_at' => now()->addWeek(),
        ];
    }

    public function discord(): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => 'discord',
        ]);
    }

    public function roblox(): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => 'roblox',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
