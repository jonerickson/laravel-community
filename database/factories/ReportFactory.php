<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'reportable_type' => 'App\Models\User',
            'reportable_id' => User::factory(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'additional_info' => fake()->optional()->sentence(),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_notes' => null,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => fake()->randomElement(['approved', 'rejected']),
            'reviewed_by' => User::factory(),
            'reviewed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'admin_notes' => fake()->optional()->sentence(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => User::factory(),
            'reviewed_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_by' => User::factory(),
            'reviewed_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'admin_notes' => fake()->sentence(),
        ]);
    }
}
