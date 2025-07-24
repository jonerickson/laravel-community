<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'commentable_type' => Post::class,
            'commentable_id' => Post::factory(),
            'content' => $this->faker->paragraph(),
            'is_approved' => $this->faker->boolean(80),
            'created_by' => User::factory(),
            'parent_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }

    public function reply(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Comment::factory(),
        ]);
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn (array $attributes) => [
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);
    }
}
