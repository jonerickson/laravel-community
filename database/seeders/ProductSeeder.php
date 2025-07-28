<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $productCategory = ProductCategory::factory()->state([
            'name' => $name = 'Product Category 1',
            'slug' => Str::slug($name),
        ])->create();

        Product::factory()
            ->count(5)
            ->featured()
            ->recycle($productCategory)
            ->hasAttached($productCategory, relationship: 'categories')
            ->product()
            ->state(new Sequence(
                fn (Sequence $sequence) => [
                    'name' => $name = "Product $sequence->index",
                    'slug' => Str::slug($name),
                    'featured_image' => "boilerplate/product-$sequence->index.jpg",
                    'stripe_product_id' => null,
                ],
            ))
            ->create();

        $subscriptionCategory = ProductCategory::factory()->state([
            'name' => $name = 'Subscription Category 1',
            'slug' => Str::slug($name),
        ])->create();

        Product::factory()
            ->count(3)
            ->recycle($subscriptionCategory)
            ->hasAttached($subscriptionCategory, relationship: 'categories')
            ->subscription()
            ->state(new Sequence(
                fn (Sequence $sequence) => [
                    'name' => $name = "Subscription $sequence->index",
                    'slug' => Str::slug($name),
                    'featured_image' => null,
                    'stripe_product_id' => null,
                ],
            ))
            ->create();
    }
}
