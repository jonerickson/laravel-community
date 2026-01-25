<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

test('cart page renders for guests with empty cart', function (): void {
    $response = $this->get('/store/cart');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/shopping-cart')
        ->has('cartItems', 0)
        ->where('cartCount', 0));
});

test('cart page renders for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/store/cart');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/shopping-cart'));
});

test('cart page shows empty cart for user without items', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/store/cart');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/shopping-cart')
        ->has('cartItems', 0)
        ->where('cartCount', 0));
});

test('cart page shows items for user with cart', function (): void {
    $user = User::factory()->create();

    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['name' => 'Test Cart Product']);

    $product->categories()->attach($category);

    $price = Price::factory()
        ->active()
        ->default()
        ->for($product)
        ->create([
            'name' => 'Standard',
            'is_visible' => true,
            'amount' => 2500,
        ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->get('/store/cart');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/shopping-cart')
        ->has('cartItems', 1)
        ->where('cartCount', 1)
        ->where('cartItems.0.name', 'Test Cart Product')
        ->where('cartItems.0.quantity', 2));
});

test('cart page shows multiple items', function (): void {
    $user = User::factory()->create();

    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product1 = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['name' => 'First Product']);

    $product1->categories()->attach($category);

    $price1 = Price::factory()
        ->active()
        ->default()
        ->for($product1)
        ->create(['is_visible' => true]);

    $product2 = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['name' => 'Second Product']);

    $product2->categories()->attach($category);

    $price2 = Price::factory()
        ->active()
        ->default()
        ->for($product2)
        ->create(['is_visible' => true]);

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

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->get('/store/cart');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/shopping-cart')
        ->has('cartItems', 2)
        ->where('cartCount', 2));
});

test('cart page creates pending order for authenticated user', function (): void {
    $user = User::factory()->create();

    expect(Order::query()->where('user_id', $user->id)->count())->toBe(0);

    $response = $this->actingAs($user)->get('/store/cart');

    $response->assertOk();

    expect(Order::query()->where('user_id', $user->id)->where('status', OrderStatus::Pending)->count())->toBe(1);
});

test('cart page does not create order for guest', function (): void {
    $initialOrderCount = Order::count();

    $response = $this->get('/store/cart');

    $response->assertOk();

    expect(Order::count())->toBe($initialOrderCount);
});

test('cart page defers order data', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->get('/store/cart');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/shopping-cart'));
});

test('cart destroy clears cart for authenticated user', function (): void {
    $user = User::factory()->create();

    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create();

    $product->categories()->attach($category);

    $price = Price::factory()
        ->active()
        ->default()
        ->for($product)
        ->create(['is_visible' => true]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $this->mock(PaymentManager::class, function ($mock) use ($order): void {
        $mock->shouldReceive('cancelOrder')
            ->once()
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $order->id))
            ->andReturn(true);
    });

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->delete('/store/cart');

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Your cart has been successfully emptied.');
});

test('cart destroy requires authentication', function (): void {
    $response = $this->delete('/store/cart');

    $response->assertRedirect(route('login'));
});

test('cart destroy deletes pending order', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->mock(PaymentManager::class, function ($mock): void {
        $mock->shouldReceive('cancelOrder')
            ->once()
            ->andReturn(true);
    });

    expect(Order::find($order->id))->not()->toBeNull();

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->delete('/store/cart');

    $response->assertRedirect();

    expect(Order::find($order->id))->toBeNull();
});

test('cart destroy removes session pending order id', function (): void {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->mock(PaymentManager::class, function ($mock): void {
        $mock->shouldReceive('cancelOrder')
            ->once()
            ->andReturn(true);
    });

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->delete('/store/cart');

    $response->assertRedirect();
    $response->assertSessionMissing('pending_order_id');
});

test('cart does not show other user orders', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['name' => 'Other User Product']);

    $product->categories()->attach($category);

    $price = Price::factory()
        ->active()
        ->default()
        ->for($product)
        ->create(['is_visible' => true]);

    $otherOrder = Order::factory()->create([
        'user_id' => $user2->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $otherOrder->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user1)
        ->withSession(['pending_order_id' => $otherOrder->id])
        ->get('/store/cart');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/shopping-cart')
        ->has('cartItems', 0)
        ->where('cartCount', 0));
});

test('cart only shows pending orders', function (): void {
    $user = User::factory()->create();

    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['name' => 'Completed Order Product']);

    $product->categories()->attach($category);

    $price = Price::factory()
        ->active()
        ->default()
        ->for($product)
        ->create(['is_visible' => true]);

    $completedOrder = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Succeeded,
    ]);

    OrderItem::create([
        'order_id' => $completedOrder->id,
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $completedOrder->id])
        ->get('/store/cart');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/shopping-cart')
        ->has('cartItems', 0)
        ->where('cartCount', 0));
});
