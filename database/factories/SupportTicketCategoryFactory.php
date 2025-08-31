<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SupportTicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicketCategory>
 */
class SupportTicketCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->hexColor(),
            'order' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
