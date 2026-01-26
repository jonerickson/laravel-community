<?php

declare(strict_types=1);

use App\Data\CustomerData;
use App\Enums\OrderStatus;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Laravel\Passport\Passport;
use Mockery\MockInterface;

beforeEach(function (): void {
    $this->appUrl = config('app.url');
});

test('checkout requires authentication', function (): void {
    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertUnauthorized();
});

test('checkout requires verified email', function (): void {
    $user = User::factory()->unverified()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertForbidden();
});

test('checkout returns error when customer creation fails', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $this->mock(PaymentManager::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(null);
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(false);
    });

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertUnauthorized();
    $response->assertJson([
        'success' => false,
        'message' => 'Unable to create/fetch your customer account.',
    ]);
});

test('checkout returns error with empty cart', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $this->mock(PaymentManager::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(CustomerData::from([
                'id' => 'cus_test123',
                'email' => $user->email,
            ]));
    });

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => 'Your cart is currently empty.',
    ]);
});

test('checkout handles zero amount order by completing immediately', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'external_product_id' => 'prod_test123',
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 0,
        'external_price_id' => 'price_test123',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $this->mock(PaymentManager::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldIgnoreMissing();
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(CustomerData::from([
                'id' => 'cus_test123',
                'email' => $user->email,
            ]));
    });

    $response = $this->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Your order was completed successfully.',
    ]);
    $response->assertJsonPath('data.checkoutUrl', route('settings.orders'));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Succeeded);
    expect($order->amount_paid)->toBe(0.0);
});

test('checkout returns error when price not configured', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'external_product_id' => 'prod_test123',
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 1000,
        'external_price_id' => null,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $this->mock(PaymentManager::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(CustomerData::from([
                'id' => 'cus_test123',
                'email' => $user->email,
            ]));
    });

    $response = $this->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
    ]);
    $response->assertJsonFragment([
        'price' => ['No prices are configured for '.$product->name.'.'],
    ]);
});

test('checkout returns error when checkout session creation fails', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'external_product_id' => 'prod_test123',
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 1000,
        'external_price_id' => 'price_test123',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $this->mock(PaymentManager::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(CustomerData::from([
                'id' => 'cus_test123',
                'email' => $user->email,
            ]));
        $mock->shouldReceive('getCheckoutUrl')
            ->once()
            ->andReturn(false);
    });

    $response = $this->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'Failed to create checkout session. Please try again.',
    ]);
});

test('checkout creates stripe session and returns checkout url', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'external_product_id' => 'prod_test123',
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 2500,
        'external_price_id' => 'price_test123',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $expectedCheckoutUrl = 'https://checkout.stripe.com/pay/cs_test_abc123';

    $this->mock(PaymentManager::class, function (MockInterface $mock) use ($user, $order, $expectedCheckoutUrl): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(CustomerData::from([
                'id' => 'cus_test123',
                'email' => $user->email,
            ]));
        $mock->shouldReceive('getCheckoutUrl')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $order->id))
            ->andReturn($expectedCheckoutUrl);
    });

    $response = $this->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);
    $response->assertJsonPath('data.checkoutUrl', $expectedCheckoutUrl);
});

test('checkout with existing customer does not create new customer', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'external_product_id' => 'prod_test123',
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 1500,
        'external_price_id' => 'price_test123',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $this->mock(PaymentManager::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(CustomerData::from([
                'id' => 'cus_existing456',
                'email' => $user->email,
            ]));
        $mock->shouldReceive('createCustomer')
            ->never();
        $mock->shouldReceive('getCheckoutUrl')
            ->once()
            ->andReturn('https://checkout.stripe.com/pay/cs_test_xyz');
    });

    $response = $this->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertOk();
});

test('checkout creates customer when not exists', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()->product()->approved()->visible()->active()->create([
        'external_product_id' => 'prod_test123',
    ]);
    $product->categories()->attach($category);
    $price = Price::factory()->active()->default()->for($product)->create([
        'is_visible' => true,
        'amount' => 3000,
        'external_price_id' => 'price_test123',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $this->mock(PaymentManager::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('getCustomer')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(null);
        $mock->shouldReceive('createCustomer')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(true);
        $mock->shouldReceive('getCheckoutUrl')
            ->once()
            ->andReturn('https://checkout.stripe.com/pay/cs_test_new');
    });

    $response = $this->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertOk();
});

test('checkout with multiple items processes all items', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $category = ProductCategory::factory()->active()->visible()->create();

    $product1 = Product::factory()->product()->approved()->visible()->active()->create([
        'name' => 'Product One',
        'external_product_id' => 'prod_test1',
    ]);
    $product1->categories()->attach($category);
    $price1 = Price::factory()->active()->default()->for($product1)->create([
        'is_visible' => true,
        'amount' => 1000,
        'external_price_id' => 'price_test1',
    ]);

    $product2 = Product::factory()->product()->approved()->visible()->active()->create([
        'name' => 'Product Two',
        'external_product_id' => 'prod_test2',
    ]);
    $product2->categories()->attach($category);
    $price2 = Price::factory()->active()->default()->for($product2)->create([
        'is_visible' => true,
        'amount' => 2000,
        'external_price_id' => 'price_test2',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price1->id,
        'quantity' => 1,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price2->id,
        'quantity' => 1,
    ]);

    $expectedCheckoutUrl = 'https://checkout.stripe.com/pay/cs_test_multi';

    $this->mock(PaymentManager::class, function (MockInterface $mock) use ($user, $expectedCheckoutUrl): void {
        $mock->shouldReceive('getCustomer')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn(CustomerData::from([
                'id' => 'cus_test123',
                'email' => $user->email,
            ]));
        $mock->shouldReceive('getCheckoutUrl')
            ->once()
            ->andReturn($expectedCheckoutUrl);
    });

    $response = $this->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/checkout');

    $response->assertOk();
    $response->assertJsonPath('data.checkoutUrl', $expectedCheckoutUrl);
});
