<?php

declare(strict_types=1);

use App\Managers\PaymentManager;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

test('guest cannot start subscription', function (): void {
    $response = $this->post(route('onboarding.subscribe'), [
        'price_id' => 1,
    ]);

    $response->assertRedirect(route('login'));
});

test('authenticated user can start subscription', function (): void {
    $user = User::factory()->notOnboarded()->create();
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()
        ->subscription()
        ->approved()
        ->active()
        ->visible()
        ->withStripe()
        ->create();
    $product->categories()->attach($category);
    $price = Price::factory()
        ->recurring()
        ->active()
        ->withStripe()
        ->create([
            'product_id' => $product->id,
            'is_visible' => true,
        ]);

    $paymentManagerMock = $this->mock(PaymentManager::class);
    $paymentManagerMock->shouldIgnoreMissing();
    $paymentManagerMock
        ->shouldReceive('startSubscription')
        ->once()
        ->andReturn('https://checkout.stripe.com/test');

    $response = $this->actingAs($user)->post(route('onboarding.subscribe'), [
        'price_id' => $price->id,
    ]);

    $response->assertRedirect('https://checkout.stripe.com/test');
});

test('subscription requires price_id', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->post(route('onboarding.subscribe'), []);

    $response->assertSessionHasErrors(['price_id']);
});

test('subscription requires valid price_id', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->post(route('onboarding.subscribe'), [
        'price_id' => 99999,
    ]);

    $response->assertSessionHasErrors(['price_id']);
});

test('subscription requires integer price_id', function (): void {
    $user = User::factory()->notOnboarded()->create();

    $response = $this->actingAs($user)->post(route('onboarding.subscribe'), [
        'price_id' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['price_id']);
});

test('subscription fails when checkout url is not returned', function (): void {
    $user = User::factory()->notOnboarded()->create();
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()
        ->subscription()
        ->approved()
        ->active()
        ->visible()
        ->withStripe()
        ->create();
    $product->categories()->attach($category);
    $price = Price::factory()
        ->recurring()
        ->active()
        ->withStripe()
        ->create([
            'product_id' => $product->id,
            'is_visible' => true,
        ]);

    $paymentManagerMock = $this->mock(PaymentManager::class);
    $paymentManagerMock->shouldIgnoreMissing();
    $paymentManagerMock
        ->shouldReceive('startSubscription')
        ->once()
        ->andReturn(false);

    $response = $this->actingAs($user)->post(route('onboarding.subscribe'), [
        'price_id' => $price->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'We were unable to start your subscription. Please try again later.');
    $response->assertSessionHas('messageVariant', 'error');
});

test('subscription is forbidden for inactive product', function (): void {
    $user = User::factory()->notOnboarded()->create();
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()
        ->subscription()
        ->approved()
        ->inactive()
        ->visible()
        ->withStripe()
        ->create();
    $product->categories()->attach($category);
    $price = Price::factory()
        ->recurring()
        ->active()
        ->withStripe()
        ->create([
            'product_id' => $product->id,
            'is_visible' => true,
        ]);

    $response = $this->actingAs($user)->post(route('onboarding.subscribe'), [
        'price_id' => $price->id,
    ]);

    $response->assertForbidden();
});

test('subscription is forbidden for unapproved product', function (): void {
    $user = User::factory()->notOnboarded()->create();
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()
        ->subscription()
        ->pending()
        ->active()
        ->visible()
        ->withStripe()
        ->create();
    $product->categories()->attach($category);
    $price = Price::factory()
        ->recurring()
        ->active()
        ->withStripe()
        ->create([
            'product_id' => $product->id,
            'is_visible' => true,
        ]);

    $response = $this->actingAs($user)->post(route('onboarding.subscribe'), [
        'price_id' => $price->id,
    ]);

    $response->assertForbidden();
});

test('subscription creates order with item', function (): void {
    $user = User::factory()->notOnboarded()->create();
    $category = ProductCategory::factory()->active()->visible()->create();
    $product = Product::factory()
        ->subscription()
        ->approved()
        ->active()
        ->visible()
        ->withStripe()
        ->create();
    $product->categories()->attach($category);
    $price = Price::factory()
        ->recurring()
        ->active()
        ->withStripe()
        ->create([
            'product_id' => $product->id,
            'is_visible' => true,
        ]);

    $paymentManagerMock = $this->mock(PaymentManager::class);
    $paymentManagerMock->shouldIgnoreMissing();
    $paymentManagerMock
        ->shouldReceive('startSubscription')
        ->once()
        ->andReturn('https://checkout.stripe.com/test');

    $this->actingAs($user)->post(route('onboarding.subscribe'), [
        'price_id' => $price->id,
    ]);

    // Verify an order with the correct item was created
    $order = $user->orders()->whereHas('items', function ($query) use ($price): void {
        $query->where('price_id', $price->id);
    })->first();

    expect($order)->not->toBeNull();
    expect($order->items()->where('price_id', $price->id)->exists())->toBeTrue();
});
