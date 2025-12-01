<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PriceType;
use App\Enums\SubscriptionInterval;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Price>
 */
class PriceFactory extends Factory
{
    public function definition(): array
    {
        $isRecurring = $this->faker->boolean(40);

        return [
            'reference_id' => $this->faker->uuid(),
            'product_id' => Product::factory(),
            'name' => $this->faker->randomElement([
                'Standard Price',
                'Premium',
                'Basic',
                'Pro',
                'Enterprise',
                'Starter',
            ]),
            'amount' => $this->faker->numberBetween(1000, 9999),
            'currency' => 'USD',
            'interval' => $isRecurring ? $this->faker->randomElement(SubscriptionInterval::cases()) : null,
            'interval_count' => $isRecurring ? $this->faker->numberBetween(1, 12) : 1,
            'external_price_id' => $this->faker->optional(0.7)->regexify('price_[A-Za-z0-9]{14}'),
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
            'name' => 'One-Time',
            'type' => PriceType::OneTime,
            'interval' => null,
            'interval_count' => 1,
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PriceType::Recurring,
            'interval' => $this->faker->randomElement(SubscriptionInterval::cases()),
            'interval_count' => $this->faker->numberBetween(1, 12),
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Monthly',
            'type' => PriceType::Recurring,
            'interval' => SubscriptionInterval::Monthly,
            'interval_count' => 1,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Yearly',
            'type' => PriceType::Recurring,
            'interval' => SubscriptionInterval::Yearly,
            'interval_count' => 1,
        ]);
    }

    public function withStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'external_price_id' => $this->faker->regexify('price_[A-Za-z0-9]{14}'),
        ]);
    }

    public function withStripePriceId(string $envKey): static
    {
        return $this->state(fn (array $attributes) => [
            'external_price_id' => env($envKey),
        ]);
    }

    public function withoutStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'external_price_id' => null,
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
