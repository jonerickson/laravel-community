<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductTaxCode;
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
            'reference_id' => $this->faker->uuid(),
            'name' => $name = "$typeName {$this->faker->numberBetween(1, 10)}",
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'type' => $type,
            'tax_code' => $this->faker->randomElement(ProductTaxCode::class),
            'is_featured' => $this->faker->boolean(0.2),
            'external_product_id' => $this->faker->optional(0.6)->regexify('prod_[A-Za-z0-9]{14}'),
            'featured_image' => $this->faker->optional(0.8)->imageUrl(800, 600, 'products'),
        ];
    }

    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name = "Product {$this->faker->unique()->numberBetween(1, 10)}",
            'slug' => Str::slug($name),
            'type' => ProductType::Product,
        ]);
    }

    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name = "Subscription {$this->faker->unique()->numberBetween(1, 10)}",
            'slug' => Str::slug($name),
            'type' => ProductType::Subscription,
        ]);
    }

    public function withStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'external_product_id' => $this->faker->regexify('prod_[A-Za-z0-9]{14}'),
        ]);
    }

    public function withoutStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'external_product_id' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function notFeatured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => false,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => ProductApprovalStatus::Approved,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => ProductApprovalStatus::Pending,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => ProductApprovalStatus::Rejected,
        ]);
    }

    public function visible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => true,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
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
