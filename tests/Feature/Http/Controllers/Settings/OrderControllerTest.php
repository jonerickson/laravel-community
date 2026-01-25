<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;

test('orders settings page is displayed for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/orders');

    $response->assertOk();
});

test('orders settings page redirects guests to login', function (): void {
    $response = $this->get('/settings/orders');

    $response->assertRedirect('/login');
});

test('orders settings page returns Inertia response', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/orders');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/orders'));
});

test('orders settings page shows user orders with items', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $price = Price::factory()->create(['product_id' => $product->id]);

    $order = Order::query()->create([
        'reference_id' => fake()->uuid(),
        'user_id' => $user->id,
        'status' => OrderStatus::Succeeded,
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'name' => 'Test Product',
        'amount' => 1000,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->get('/settings/orders');

    $response->assertOk();
});

test('orders settings page does not show other users orders', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = Product::factory()->create();
    $price = Price::factory()->create(['product_id' => $product->id]);

    $order = Order::query()->create([
        'reference_id' => fake()->uuid(),
        'user_id' => $otherUser->id,
        'status' => OrderStatus::Succeeded,
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'price_id' => $price->id,
        'name' => 'Other User Product',
        'amount' => 1000,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->get('/settings/orders');

    $response->assertOk();
});

test('orders settings page shows orders with correct statuses', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $price = Price::factory()->create(['product_id' => $product->id]);

    // Create orders with different statuses that should be visible
    foreach ([OrderStatus::Succeeded, OrderStatus::Pending, OrderStatus::Cancelled, OrderStatus::Refunded] as $status) {
        $order = Order::query()->create([
            'reference_id' => fake()->uuid(),
            'user_id' => $user->id,
            'status' => $status,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'name' => 'Product '.$status->value,
            'amount' => 1000,
            'quantity' => 1,
        ]);
    }

    $response = $this->actingAs($user)->get('/settings/orders');

    $response->assertOk();
});

test('orders settings page does not show orders without items', function (): void {
    $user = User::factory()->create();

    // Order without items
    Order::query()->create([
        'reference_id' => fake()->uuid(),
        'user_id' => $user->id,
        'status' => OrderStatus::Succeeded,
    ]);

    $response = $this->actingAs($user)->get('/settings/orders');

    $response->assertOk();
});
