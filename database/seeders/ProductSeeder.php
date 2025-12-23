<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PriceType;
use App\Enums\ProductApprovalStatus;
use App\Enums\SubscriptionInterval;
use App\Models\Group;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $productCategory = ProductCategory::factory()->visible()->active()->state([
            'name' => $name = 'Product Category 1',
            'slug' => Str::slug($name),
            'featured_image' => 'boilerplate/product-category-1.jpg',
        ])->create();

        foreach (range(0, 3) as $index) {
            Product::factory()
                ->approved()
                ->featured()
                ->recycle($productCategory)
                ->hasAttached($productCategory, relationship: 'categories')
                ->hasAttached(Group::firstOrCreate(['name' => 'Customers']), relationship: 'groups')
                ->product()
                ->state(function () use ($index) {
                    $state = [
                        'name' => $name = "Product $index",
                        'slug' => Str::slug($name),
                        'featured_image' => "boilerplate/product-$index.jpg",
                        'external_product_id' => env(sprintf('STRIPE_PRODUCT_%s', $index)),
                    ];

                    if ($index === 0) {
                        $state['commission_rate'] = 0.20;
                        $state['seller_id'] = User::first()?->getKey() ?? User::factory()->create()->getKey();
                        $state['approval_status'] = ProductApprovalStatus::Approved;
                        $state['approved_by'] = User::first()?->getKey() ?? User::factory()->create()->getKey();
                        $state['approved_at'] = now();
                    }

                    return $state;
                })
                ->has(Price::factory()
                    ->count(2)
                    ->oneTime()
                    ->active()
                    ->state(new Sequence(
                        fn (Sequence $sequence) => [
                            'is_default' => $sequence->index === 0,
                            'external_price_id' => env(sprintf('STRIPE_PRODUCT_%s_PRICE_%s', $index, $sequence->index)),
                        ]
                    ))
                )
                ->create();
        }

        $subscriptionCategory = ProductCategory::factory()->hidden()->active()->state([
            'name' => $name = 'Subscription Category 1',
            'slug' => Str::slug($name),
        ])->create();

        foreach (range(0, 2) as $index) {
            Product::factory()
                ->approved()
                ->recycle($subscriptionCategory)
                ->hasAttached($subscriptionCategory, relationship: 'categories')
                ->hasAttached(Group::firstOrCreate(['name' => 'Customers']), relationship: 'groups')
                ->subscription()
                ->state([
                    'name' => $name = "Subscription $index",
                    'slug' => Str::slug($name),
                    'featured_image' => null,
                    'external_product_id' => env(sprintf('STRIPE_SUBSCRIPTION_%s', $index)),
                    'is_featured' => $index === 1,
                    'is_subscription_only' => true,
                    'metadata' => [
                        'features' => [
                            'An example of feature 1.',
                            'An example of feature 2.',
                            'An example of feature 3.',
                        ],
                    ],
                ])
                ->has(Price::factory()
                    ->count(2)
                    ->active()
                    ->state(new Sequence(
                        fn (Sequence $sequence) => [
                            'is_default' => $sequence->index === 0,
                            'external_price_id' => env(sprintf('STRIPE_SUBSCRIPTION_%s_PRICE_%s', $index, $sequence->index)),
                            'type' => PriceType::Recurring,
                            'interval' => $sequence->index === 0 ? SubscriptionInterval::Monthly : SubscriptionInterval::Yearly,
                            'interval_count' => 1,
                            'amount' => match ($index) {
                                0 => $sequence->index === 0 ? 10 : 100,
                                1 => $sequence->index === 0 ? 20 : 200,
                                2 => $sequence->index === 0 ? 30 : 300,
                            },
                        ]
                    ))
                )
                ->create();
        }
    }
}
