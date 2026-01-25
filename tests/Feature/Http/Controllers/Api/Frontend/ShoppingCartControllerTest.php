<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

beforeEach(function (): void {
    $this->appUrl = config('app.url');
});

test('add item to cart successfully', function (): void {
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
        ->create(['name' => 'Test Product']);

    $product->categories()->attach($category);

    $price = Price::factory()
        ->active()
        ->default()
        ->for($product)
        ->create([
            'name' => 'Standard',
            'is_visible' => true,
            'amount' => 1000,
        ]);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price->id,
            'quantity' => 2,
        ]);

    $response->assertCreated();
    $response->assertJson([
        'success' => true,
        'message' => 'The item was successfully added to your cart.',
    ]);
    $response->assertJsonPath('data.cartCount', 1);
    $response->assertJsonPath('data.cartItems.0.quantity', 2);
    $response->assertJsonPath('data.cartItems.0.name', 'Test Product');

    $this->assertDatabaseHas('orders_items', [
        'price_id' => $price->id,
        'quantity' => 2,
    ]);
});

test('add item to cart creates pending order for user', function (): void {
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

    expect(Order::query()->where('user_id', $user->id)->count())->toBe(0);

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price->id,
            'quantity' => 1,
        ]);

    $response->assertCreated();

    expect(Order::query()
        ->where('user_id', $user->id)
        ->where('status', OrderStatus::Pending)
        ->count())
        ->toBe(1);
});

test('add item to cart fails with missing quantity', function (): void {
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

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price->id,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['quantity' => 'A quantity is required.']);
});

test('add item to cart fails with invalid price id', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => 99999,
            'quantity' => 1,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['price_id' => 'The selected price is invalid.']);
});

test('add item to cart fails with quantity below minimum', function (): void {
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

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price->id,
            'quantity' => 0,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['quantity' => 'The quantity must be at least 1.']);
});

test('add item to cart fails with quantity above maximum', function (): void {
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

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price->id,
            'quantity' => 100,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['quantity' => 'The quantity cannot exceed 99.']);
});

test('add item to cart fails when item already in cart', function (): void {
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

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price->id,
            'quantity' => 2,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['price_id' => 'This item is already in your cart. Please adjust the quantity instead.']);
});

test('update item quantity in cart successfully', function (): void {
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
        ->create(['name' => 'Test Product']);

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

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->putJson('/api/cart', [
            'price_id' => $price->id,
            'quantity' => 5,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Your cart has been successfully updated.',
    ]);
    $response->assertJsonPath('data.cartItems.0.quantity', 5);

    $this->assertDatabaseHas('orders_items', [
        'order_id' => $order->id,
        'price_id' => $price->id,
        'quantity' => 5,
    ]);
});

test('update item removes other items from cart', function (): void {
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
        ->withHeader('referer', $this->appUrl)
        ->putJson('/api/cart', [
            'price_id' => $price1->id,
            'quantity' => 3,
        ]);

    $response->assertOk();
    $response->assertJsonPath('data.cartCount', 1);

    $this->assertDatabaseHas('orders_items', [
        'order_id' => $order->id,
        'price_id' => $price1->id,
        'quantity' => 3,
    ]);

    $this->assertDatabaseMissing('orders_items', [
        'order_id' => $order->id,
        'price_id' => $price2->id,
    ]);
});

test('update item fails with missing quantity', function (): void {
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

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->putJson('/api/cart', [
            'price_id' => $price->id,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['quantity' => 'A quantity is required.']);
});

test('update item fails with invalid price id', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->putJson('/api/cart', [
            'price_id' => 99999,
            'quantity' => 1,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['price_id' => 'The selected price is invalid.']);
});

test('remove item from cart successfully', function (): void {
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
        'quantity' => 2,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->deleteJson('/api/cart', [
            'price_id' => $price->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'The item was successfully removed from your cart.',
    ]);
    $response->assertJsonPath('data.cartCount', 0);

    $this->assertDatabaseMissing('orders_items', [
        'order_id' => $order->id,
        'price_id' => $price->id,
    ]);
});

test('remove item from cart fails with invalid price id', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->deleteJson('/api/cart', [
            'price_id' => 99999,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['price_id' => 'The selected price is invalid.']);
});

test('cart total reflects item prices', function (): void {
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
        ->create(['name' => 'Test Product']);

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

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price->id,
            'quantity' => 3,
        ]);

    $response->assertCreated();
    $response->assertJsonPath('data.cartItems.0.selectedPrice.amount', 2500);
    $response->assertJsonPath('data.cartItems.0.quantity', 3);
});

test('guest can add item to cart without order creation', function (): void {
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

    $initialOrderCount = Order::count();

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price->id,
            'quantity' => 1,
        ]);

    $response->assertCreated();
    $response->assertJsonPath('data.cartCount', 0);

    expect(Order::count())->toBe($initialOrderCount);
});

test('multiple items can be added to cart sequentially', function (): void {
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
        ->create(['name' => 'Product A']);

    $product1->categories()->attach($category);

    $price1 = Price::factory()
        ->active()
        ->default()
        ->for($product1)
        ->create(['is_visible' => true, 'amount' => 1000]);

    $product2 = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['name' => 'Product B']);

    $product2->categories()->attach($category);

    $price2 = Price::factory()
        ->active()
        ->default()
        ->for($product2)
        ->create(['is_visible' => true, 'amount' => 2000]);

    $response1 = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price1->id,
            'quantity' => 1,
        ]);

    $response1->assertCreated();
    $response1->assertJsonPath('data.cartCount', 1);

    $orderId = Order::query()->where('user_id', $user->id)->first()->id;

    $response2 = $this->actingAs($user)
        ->withSession(['pending_order_id' => $orderId])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price2->id,
            'quantity' => 2,
        ]);

    $response2->assertCreated();
    $response2->assertJsonPath('data.cartCount', 2);
});

test('cart items are sorted by name', function (): void {
    $user = User::factory()->create();

    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $productZ = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['name' => 'Zebra Product']);

    $productZ->categories()->attach($category);

    $priceZ = Price::factory()
        ->active()
        ->default()
        ->for($productZ)
        ->create(['is_visible' => true]);

    $productA = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['name' => 'Apple Product']);

    $productA->categories()->attach($category);

    $priceA = Price::factory()
        ->active()
        ->default()
        ->for($productA)
        ->create(['is_visible' => true]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'price_id' => $priceZ->id,
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['pending_order_id' => $order->id])
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $priceA->id,
            'quantity' => 1,
        ]);

    $response->assertCreated();
    $response->assertJsonPath('data.cartItems.0.name', 'Apple Product');
    $response->assertJsonPath('data.cartItems.1.name', 'Zebra Product');
});

test('add item with non-integer quantity fails', function (): void {
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

    $response = $this->actingAs($user)
        ->withHeader('referer', $this->appUrl)
        ->postJson('/api/cart', [
            'price_id' => $price->id,
            'quantity' => 'abc',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['quantity' => 'The quantity must be a valid number.']);
});
