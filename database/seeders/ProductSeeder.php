<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
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

        $products = Product::factory()
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
                    'stripe_product_id' => env('STRIPE_PRODUCT_'.($sequence->index + 1)),
                ],
            ))
            ->create();

        foreach ($products as $index => $product) {
            $priceKey1 = 'STRIPE_PRODUCT_'.($index + 1).'_PRICE_1';
            $priceKey2 = 'STRIPE_PRODUCT_'.($index + 1).'_PRICE_2';

            if (env($priceKey1)) {
                ProductPrice::factory()
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
                ProductPrice::factory()
                    ->for($product)
                    ->withStripePriceId($priceKey2)
                    ->oneTime()
                    ->active()
                    ->create([
                        'name' => 'Yearly',
                        'interval_count' => 1,
                        'interval' => 'year',
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
                    'stripe_product_id' => null,
                ],
            ))
            ->create();

        foreach ($subscriptions as $index => $subscription) {
            $priceKey1 = 'STRIPE_SUBSCRIPTION_'.($index + 1).'_PRICE_1';
            $priceKey2 = 'STRIPE_SUBSCRIPTION_'.($index + 1).'_PRICE_2';

            if (env($priceKey1)) {
                ProductPrice::factory()
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
                ProductPrice::factory()
                    ->for($subscription)
                    ->withStripePriceId($priceKey2)
                    ->yearly()
                    ->active()
                    ->create([
                        'name' => 'Yearly',
                        'interval_count' => 1,
                        'interval' => 'year',
                    ]);
            }
        }
    }
}
