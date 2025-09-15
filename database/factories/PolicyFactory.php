<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Policy;
use App\Models\PolicyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Policy>
 */
class PolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(2, true),
            'description' => $this->faker->paragraph(),
            'content' => $this->faker->paragraphs(3, asText: true),
            'version' => $this->faker->numerify('v#.#.#'),
            'policy_category_id' => PolicyCategory::factory(),
            'is_active' => $this->faker->boolean(),
            'effective_at' => now(),
        ];
    }
}
