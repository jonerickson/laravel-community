<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'title' => ucwords($title),
            'description' => fake()->sentence(),
            'html_content' => '<div class="container"><h1>'.ucwords($title).'</h1><p>'.fake()->paragraphs(3, true).'</p></div>',
            'css_content' => '.container { max-width: 1200px; margin: 0 auto; padding: 2rem; }',
            'js_content' => 'console.log("Page loaded");',
            'is_published' => fake()->boolean(80),
            'published_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-1 year', 'now') : null,
            'show_in_navigation' => fake()->boolean(50),
            'navigation_label' => fake()->boolean(30) ? fake()->words(2, true) : null,
            'navigation_order' => fake()->numberBetween(0, 100),
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

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function inNavigation(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_in_navigation' => true,
        ]);
    }
}
