<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'path' => $this->faker->imageUrl(),
        ];
    }
}
