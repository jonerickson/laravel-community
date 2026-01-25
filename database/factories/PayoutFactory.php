<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayoutDriver;
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
            'seller_id' => User::factory(),
            'amount' => fake()->numberBetween(1000, 50000),
            'status' => fake()->randomElement(PayoutStatus::cases()),
            'payout_method' => fake()->randomElement(PayoutDriver::cases()),
            'external_payout_id' => fake()->optional()->uuid(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => fake()->optional(0.7)->randomElement(User::pluck('id')->toArray()),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::Pending,
            'created_by' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::Completed,
            'created_by' => User::factory(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::Failed,
            'created_by' => User::factory(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::Cancelled,
            'created_by' => null,
        ]);
    }
}
