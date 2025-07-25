<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PolicyCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyCategory>
 */
class PolicyCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'is_active' => $this->faker->boolean(),
        ];
    }
}
