<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SubscriptionInterval;
use App\Models\Image;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $productCategory = ProductCategory::factory()->state([
            'name' => $name = 'Product Category 1',
            'slug' => Str::slug($name),
        ])->has(Image::factory()->state([
            'path' => 'boilerplate/product-category-1.jpg',
        ]))->create();

        $products = Product::factory()
            ->count(4)
            ->featured()
            ->recycle($productCategory)
            ->hasAttached($productCategory, relationship: 'categories')
            ->product()
            ->state(new Sequence(
                fn (Sequence $sequence) => [
                    'name' => $name = "Product $sequence->index",
                    'slug' => Str::slug($name),
                    'featured_image' => "boilerplate/product-$sequence->index.jpg",
                    'external_product_id' => env('STRIPE_PRODUCT_'.($sequence->index + 1)),
                ],
            ))
            ->create();

        foreach ($products as $index => $product) {
            $priceKey1 = 'STRIPE_PRODUCT_'.($index + 1).'_PRICE_1';
            $priceKey2 = 'STRIPE_PRODUCT_'.($index + 1).'_PRICE_2';

            if (env($priceKey1)) {
                Price::factory()
                    ->for($product)
                    ->withStripePriceId($priceKey1)
                    ->oneTime()
                    ->default()
                    ->active()
                    ->create([
                        'name' => 'Monthly',
                        'interval_count' => 1,
                        'interval' => 'month',
                    ]);
            }

            if (env($priceKey2)) {
                Price::factory()
                    ->for($product)
                    ->withStripePriceId($priceKey2)
                    ->oneTime()
                    ->active()
                    ->create([
                        'name' => 'Yearly',
                        'interval_count' => 1,
                        'interval' => SubscriptionInterval::Yearly,
                    ]);
            }
        }

        $subscriptionCategory = ProductCategory::factory()->state([
            'name' => $name = 'Subscription Category 1',
            'slug' => Str::slug($name),
        ])->create();

        $subscriptions = Product::factory()
            ->count(3)
            ->recycle($subscriptionCategory)
            ->hasAttached($subscriptionCategory, relationship: 'categories')
            ->subscription()
            ->state(new Sequence(
                fn (Sequence $sequence) => [
                    'name' => $name = "Subscription $sequence->index",
                    'slug' => Str::slug($name),
                    'featured_image' => null,
                    'external_product_id' => null,
                    'is_subscription_only' => true,
                    'metadata' => [
                        'features' => [
                            'An example of feature 1.',
                            'An example of feature 2.',
                            'An example of feature 3.',
                        ],
                    ],
                ],
            ))
            ->create();

        foreach ($subscriptions as $index => $subscription) {
            $priceKey1 = 'STRIPE_SUBSCRIPTION_'.($index + 1).'_PRICE_1';
            $priceKey2 = 'STRIPE_SUBSCRIPTION_'.($index + 1).'_PRICE_2';

            if (env($priceKey1)) {
                Price::factory()
                    ->for($subscription)
                    ->withStripePriceId($priceKey1)
                    ->monthly()
                    ->default()
                    ->active()
                    ->create([
                        'name' => 'Monthly',
                        'interval_count' => 1,
                        'interval' => 'month',
                    ]);
            }

            if (env($priceKey2)) {
                Price::factory()
                    ->for($subscription)
                    ->withStripePriceId($priceKey2)
                    ->yearly()
                    ->active()
                    ->create([
                        'name' => 'Yearly',
                        'interval_count' => 1,
                        'interval' => SubscriptionInterval::Yearly,
                    ]);
            }
        }
    }
}
