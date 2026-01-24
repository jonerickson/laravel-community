<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Read;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Read>
 */
class ReadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'readable_type' => Topic::class,
            'readable_id' => Topic::factory(),
            'created_by' => User::factory(),
        ];
    }

    public function forReadable(mixed $readable): static
    {
        return $this->state(fn (array $attributes) => [
            'readable_type' => $readable::class,
            'readable_id' => $readable->id,
        ]);
    }
}
