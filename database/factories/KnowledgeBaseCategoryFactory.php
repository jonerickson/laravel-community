<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeBaseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeBaseCategory>
 */
class KnowledgeBaseCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(rand(2, 4), true),
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(['book', 'lightbulb', 'cog', 'shield', 'rocket']),
            'color' => fake()->hexColor(),
            'is_active' => true,
            'order' => fake()->numberBetween(0, 100),
        ];
    }
}
