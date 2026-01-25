<?php

declare(strict_types=1);

use App\Actions\Payments\SwapSubscriptionAction;
use App\Data\SubscriptionData;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Managers\PaymentManager;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

describe('SwapSubscriptionAction', function (): void {
    test('returns false when price has no external_price_id', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withoutStripe()->create([
            'product_id' => $product->id,
        ]);

        $action = new SwapSubscriptionAction(
            user: $user,
            price: $price,
            prorationBehavior: ProrationBehavior::CreateProrations,
            paymentBehavior: PaymentBehavior::DefaultIncomplete,
        );

        $result = $action();

        expect($result)->toBeFalse();
    });

    test('returns false when user has no current subscription', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withStripe()->create([
            'product_id' => $product->id,
        ]);

        $this->mock(PaymentManager::class, function ($mock) use ($user): void {
            $mock->shouldReceive('currentSubscription')
                ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
                ->once()
                ->andReturn(null);
        });

        $action = new SwapSubscriptionAction(
            user: $user,
            price: $price,
            prorationBehavior: ProrationBehavior::CreateProrations,
            paymentBehavior: PaymentBehavior::DefaultIncomplete,
        );

        $result = $action();

        expect($result)->toBeFalse();
    });

    test('calls swapSubscription on PaymentManager when user has subscription', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withStripe()->create([
            'product_id' => $product->id,
        ]);

        $currentSubscription = SubscriptionData::from([
            'name' => 'default',
            'doesNotExpire' => true,
        ]);

        $swappedSubscription = SubscriptionData::from([
            'name' => 'default',
            'doesNotExpire' => true,
            'externalPriceId' => $price->external_price_id,
        ]);

        $this->mock(PaymentManager::class, function ($mock) use ($user, $price, $currentSubscription, $swappedSubscription): void {
            $mock->shouldReceive('currentSubscription')
                ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
                ->once()
                ->andReturn($currentSubscription);

            $mock->shouldReceive('swapSubscription')
                ->with(
                    Mockery::on(fn ($arg): bool => $arg->id === $user->id),
                    Mockery::on(fn ($arg): bool => $arg->id === $price->id),
                    ProrationBehavior::CreateProrations,
                    PaymentBehavior::DefaultIncomplete
                )
                ->once()
                ->andReturn($swappedSubscription);
        });

        $action = new SwapSubscriptionAction(
            user: $user,
            price: $price,
            prorationBehavior: ProrationBehavior::CreateProrations,
            paymentBehavior: PaymentBehavior::DefaultIncomplete,
        );

        $result = $action();

        expect($result)->toBeInstanceOf(SubscriptionData::class);
        expect($result->externalPriceId)->toBe($price->external_price_id);
    });

    test('returns swapped subscription data on success', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withStripe()->create([
            'product_id' => $product->id,
        ]);

        $swappedSubscription = SubscriptionData::from([
            'name' => 'default',
            'doesNotExpire' => true,
            'externalPriceId' => $price->external_price_id,
            'quantity' => 1,
        ]);

        $this->mock(PaymentManager::class, function ($mock) use ($swappedSubscription): void {
            $mock->shouldReceive('currentSubscription')
                ->once()
                ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));

            $mock->shouldReceive('swapSubscription')
                ->once()
                ->andReturn($swappedSubscription);
        });

        $action = new SwapSubscriptionAction(
            user: $user,
            price: $price,
            prorationBehavior: ProrationBehavior::CreateProrations,
            paymentBehavior: PaymentBehavior::DefaultIncomplete,
        );

        $result = $action();

        expect($result)->toBeInstanceOf(SubscriptionData::class);
        expect($result->name)->toBe('default');
        expect($result->quantity)->toBe(1);
    });

    test('respects proration behavior parameter', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withStripe()->create([
            'product_id' => $product->id,
        ]);

        $this->mock(PaymentManager::class, function ($mock): void {
            $mock->shouldReceive('currentSubscription')
                ->once()
                ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));

            $mock->shouldReceive('swapSubscription')
                ->with(
                    Mockery::any(),
                    Mockery::any(),
                    ProrationBehavior::AlwaysInvoice,
                    Mockery::any()
                )
                ->once()
                ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));
        });

        $action = new SwapSubscriptionAction(
            user: $user,
            price: $price,
            prorationBehavior: ProrationBehavior::AlwaysInvoice,
            paymentBehavior: PaymentBehavior::DefaultIncomplete,
        );

        $result = $action();

        expect($result)->toBeInstanceOf(SubscriptionData::class);
    });

    test('respects payment behavior parameter', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withStripe()->create([
            'product_id' => $product->id,
        ]);

        $this->mock(PaymentManager::class, function ($mock): void {
            $mock->shouldReceive('currentSubscription')
                ->once()
                ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));

            $mock->shouldReceive('swapSubscription')
                ->with(
                    Mockery::any(),
                    Mockery::any(),
                    Mockery::any(),
                    PaymentBehavior::ErrorIfIncomplete
                )
                ->once()
                ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));
        });

        $action = new SwapSubscriptionAction(
            user: $user,
            price: $price,
            prorationBehavior: ProrationBehavior::CreateProrations,
            paymentBehavior: PaymentBehavior::ErrorIfIncomplete,
        );

        $result = $action();

        expect($result)->toBeInstanceOf(SubscriptionData::class);
    });

    test('can be executed via static execute method', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withStripe()->create([
            'product_id' => $product->id,
        ]);

        $this->mock(PaymentManager::class, function ($mock): void {
            $mock->shouldReceive('currentSubscription')
                ->once()
                ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));

            $mock->shouldReceive('swapSubscription')
                ->once()
                ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));
        });

        $result = SwapSubscriptionAction::execute(
            $user,
            $price,
            ProrationBehavior::CreateProrations,
            PaymentBehavior::DefaultIncomplete,
        );

        expect($result)->toBeInstanceOf(SubscriptionData::class);
    });

    test('returns false from swapSubscription when payment fails', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withStripe()->create([
            'product_id' => $product->id,
        ]);

        $this->mock(PaymentManager::class, function ($mock): void {
            $mock->shouldReceive('currentSubscription')
                ->once()
                ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));

            $mock->shouldReceive('swapSubscription')
                ->once()
                ->andReturn(false);
        });

        $action = new SwapSubscriptionAction(
            user: $user,
            price: $price,
            prorationBehavior: ProrationBehavior::CreateProrations,
            paymentBehavior: PaymentBehavior::DefaultIncomplete,
        );

        $result = $action();

        expect($result)->toBeFalse();
    });

    test('supports all proration behavior types', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withStripe()->create([
            'product_id' => $product->id,
        ]);

        foreach (ProrationBehavior::cases() as $prorationBehavior) {
            $this->mock(PaymentManager::class, function ($mock) use ($prorationBehavior): void {
                $mock->shouldReceive('currentSubscription')
                    ->once()
                    ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));

                $mock->shouldReceive('swapSubscription')
                    ->with(
                        Mockery::any(),
                        Mockery::any(),
                        $prorationBehavior,
                        Mockery::any()
                    )
                    ->once()
                    ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));
            });

            $action = new SwapSubscriptionAction(
                user: $user,
                price: $price,
                prorationBehavior: $prorationBehavior,
                paymentBehavior: PaymentBehavior::DefaultIncomplete,
            );

            $result = $action();

            expect($result)->toBeInstanceOf(SubscriptionData::class);
        }
    });

    test('supports all payment behavior types', function (): void {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->active()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category);

        $price = Price::factory()->withStripe()->create([
            'product_id' => $product->id,
        ]);

        foreach (PaymentBehavior::cases() as $paymentBehavior) {
            $this->mock(PaymentManager::class, function ($mock) use ($paymentBehavior): void {
                $mock->shouldReceive('currentSubscription')
                    ->once()
                    ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));

                $mock->shouldReceive('swapSubscription')
                    ->with(
                        Mockery::any(),
                        Mockery::any(),
                        Mockery::any(),
                        $paymentBehavior
                    )
                    ->once()
                    ->andReturn(SubscriptionData::from(['name' => 'default', 'doesNotExpire' => true]));
            });

            $action = new SwapSubscriptionAction(
                user: $user,
                price: $price,
                prorationBehavior: ProrationBehavior::CreateProrations,
                paymentBehavior: $paymentBehavior,
            );

            $result = $action();

            expect($result)->toBeInstanceOf(SubscriptionData::class);
        }
    });
});
