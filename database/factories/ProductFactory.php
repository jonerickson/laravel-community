<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(ProductType::cases());
        $typeName = $type === ProductType::Product ? 'Product' : 'Subscription';

        return [
            'name' => $name = "{$typeName} {$this->faker->numberBetween(1, 10)}",
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'type' => $type,
            'stripe_product_id' => $this->faker->optional(0.6)->regexify('prod_[A-Za-z0-9]{14}'),
            'featured_image' => $this->faker->optional(0.8)->imageUrl(800, 600, 'products'),
        ];
    }

    /**
     * Create a product type.
     */
    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Product,
        ]);
    }

    /**
     * Create a subscription type.
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::Subscription,
        ]);
    }

    /**
     * Create a product with Stripe integration.
     */
    public function withStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_product_id' => $this->faker->regexify('prod_[A-Za-z0-9]{14}'),
        ]);
    }

    /**
     * Create a product without Stripe integration.
     */
    public function withoutStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_product_id' => null,
        ]);
    }
}
