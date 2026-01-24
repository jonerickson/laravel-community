<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Topic;
use App\Models\View;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<View>
 */
class ViewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'viewable_type' => Topic::class,
            'viewable_id' => Topic::factory(),
            'fingerprint_id' => $this->faker->uuid(),
            'count' => 1,
        ];
    }

    public function forViewable(mixed $viewable): static
    {
        return $this->state(fn (array $attributes) => [
            'viewable_type' => $viewable::class,
            'viewable_id' => $viewable->id,
        ]);
    }
}
