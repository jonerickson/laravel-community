<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Forum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Forum>
 */
class ForumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'icon' => $this->faker->randomElement(['forum', 'help-circle', 'message-square', 'chat', 'users']),
            'color' => $this->faker->hexColor(),
            'order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
