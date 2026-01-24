<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Fingerprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fingerprint>
 */
class FingerprintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fingerprint_id' => fake()->uuid(),
            'request_id' => fake()->optional()->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'suspect_score' => fake()->numberBetween(0, 100),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
}
