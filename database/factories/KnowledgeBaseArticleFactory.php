<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\KnowledgeBaseArticleType;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeBaseArticle>
 */
class KnowledgeBaseArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'excerpt' => fake()->optional()->paragraph(),
            'content' => collect(range(1, rand(3, 8)))
                ->map(fn () => '<p>'.fake()->paragraph(rand(3, 10)).'</p>')
                ->implode("\n"),
            'type' => fake()->randomElement(KnowledgeBaseArticleType::cases()),
            'category_id' => KnowledgeBaseCategory::factory(),
            'is_published' => fake()->boolean(80),
            'published_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
