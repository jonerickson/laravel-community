<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->numberBetween(1000, 50000),
            'status' => fake()->randomElement(PayoutStatus::cases()),
            'payout_method' => fake()->randomElement(['PayPal', 'Bank Transfer', 'Stripe', 'Check']),
            'external_payout_id' => fake()->optional()->uuid(),
            'notes' => fake()->optional()->sentence(),
            'processed_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'processed_by' => fake()->optional(0.7)->randomElement(User::pluck('id')->toArray()),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::Pending,
            'processed_at' => null,
            'processed_by' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::Completed,
            'processed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'processed_by' => User::factory(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::Failed,
            'processed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'processed_by' => User::factory(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::Cancelled,
            'processed_at' => null,
            'processed_by' => null,
        ]);
    }
}
