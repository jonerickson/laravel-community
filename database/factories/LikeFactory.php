<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Like>
 */
class LikeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'likeable_type' => Post::class,
            'likeable_id' => Post::factory(),
            'emoji' => fake()->randomElement(['👍', '❤️', '😂', '😮', '😢', '😡']),
            'created_by' => User::factory(),
        ];
    }

    public function forLikeable(mixed $likeable): static
    {
        return $this->state(fn (array $attributes) => [
            'likeable_type' => $likeable::class,
            'likeable_id' => $likeable->id,
        ]);
    }
}
