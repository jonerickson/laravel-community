<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $name = "Category {$this->faker->numberBetween(1, 10)}",
            'description' => $this->faker->text(),
            'slug' => Str::slug($name),
        ];
    }
}
