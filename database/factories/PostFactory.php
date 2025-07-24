<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence();
        $publishedAt = $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->optional(0.7)->paragraph(),
            'content' => $this->faker->paragraphs(rand(3, 8), true),
            'featured_image' => $this->faker->optional(0.6)->imageUrl(1200, 600, 'articles'),
            'is_published' => $publishedAt !== null,
            'is_featured' => $this->faker->boolean(20),
            'published_at' => $publishedAt,
            'created_by' => User::factory(),
            'metadata' => $this->faker->optional(0.3)->randomElements([
                'seo_title' => $this->faker->sentence(),
                'seo_description' => $this->faker->paragraph(),
                'tags' => $this->faker->words(rand(2, 5)),
            ], rand(1, 3)),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function withFeaturedImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured_image' => $this->faker->imageUrl(1200, 600, 'articles'),
        ]);
    }

    public function withoutFeaturedImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured_image' => null,
        ]);
    }

    public function forum(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::Forum,
        ]);
    }

    public function blog(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::Blog,
        ]);
    }
}
