<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    public function definition(): array
    {
        $isRecurring = $this->faker->boolean(40);
        $intervals = ['day', 'week', 'month', 'year'];

        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->randomElement([
                'Standard Price',
                'Premium',
                'Basic',
                'Pro',
                'Enterprise',
                'Starter',
            ]),
            'amount' => $this->faker->randomFloat(2, 5, 299),
            'currency' => 'USD',
            'interval' => $isRecurring ? $this->faker->randomElement($intervals) : null,
            'interval_count' => $isRecurring ? $this->faker->numberBetween(1, 12) : 1,
            'stripe_price_id' => $this->faker->optional(0.7)->regexify('price_[A-Za-z0-9]{14}'),
            'is_active' => $this->faker->boolean(85),
            'is_default' => false,
            'description' => $this->faker->optional(0.6)->sentence(),
            'metadata' => $this->faker->optional(0.3)->randomElements([
                'feature_1' => 'unlimited_downloads',
                'feature_2' => 'priority_support',
                'feature_3' => 'advanced_features',
            ], $this->faker->numberBetween(1, 3)),
        ];
    }

    public function oneTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'interval' => null,
            'interval_count' => 1,
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'interval' => $this->faker->randomElement(['month', 'year']),
            'interval_count' => $this->faker->numberBetween(1, 12),
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'interval' => 'month',
            'interval_count' => 1,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'interval' => 'year',
            'interval_count' => 1,
        ]);
    }

    public function withStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_price_id' => $this->faker->regexify('price_[A-Za-z0-9]{14}'),
        ]);
    }

    public function withoutStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_price_id' => null,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
