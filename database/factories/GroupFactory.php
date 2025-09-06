<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    public function definition(): array
    {
        $colors = [
            '#ef4444', // red-500
            '#f97316', // orange-500
            '#eab308', // yellow-500
            '#22c55e', // green-500
            '#06b6d4', // cyan-500
            '#3b82f6', // blue-500
            '#8b5cf6', // violet-500
            '#ec4899', // pink-500
            '#6b7280', // gray-500
        ];

        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'color' => fake()->randomElement($colors),
            'is_active' => fake()->boolean(90),
        ];
    }

    public function asDefaultMemberGroup(): self
    {
        return $this->state([
            'is_default_member' => true,
        ]);
    }

    public function asDefaultGuest(): self
    {
        return $this->state([
            'is_default_guest' => true,
        ]);
    }
}
