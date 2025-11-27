<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FilterType;
use App\Models\Whitelist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Whitelist>
 */
class WhitelistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content' => $this->faker->realText(),
            'description' => $this->faker->sentence(),
            'filter' => $this->faker->randomElement(FilterType::cases()),
            'is_regex' => false,
        ];
    }
}
