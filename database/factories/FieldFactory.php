<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Field>
 */
class FieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $name = $this->faker->name(),
            'label' => Str::title($name),
            'description' => $this->faker->text(),
            'type' => $this->faker->randomElement(FieldType::cases()),
            'is_public' => $this->faker->boolean(),
            'is_required' => $this->faker->boolean(),
        ];
    }
}
