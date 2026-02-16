<?php

declare(strict_types=1);

use App\Models\Price;
use App\Models\Product;
use App\Models\User;

test('marketplace page renders for guests', function (): void {
    $response = $this->get('/store/marketplace');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/marketplace/index'));
});

test('marketplace page renders for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/store/marketplace');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/marketplace/index'));
});

test('marketplace page shows only marketplace products', function (): void {
    $seller = User::factory()->create();

    $marketplaceProduct = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['seller_id' => $seller->id, 'name' => 'Community Product']);

    Price::factory()
        ->active()
        ->default()
        ->for($marketplaceProduct)
        ->create(['is_visible' => true]);

    $regularProduct = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['seller_id' => null, 'name' => 'Regular Product']);

    Price::factory()
        ->active()
        ->default()
        ->for($regularProduct)
        ->create(['is_visible' => true]);

    $response = $this->get('/store/marketplace');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/marketplace/index')
        ->has('products.data', 1)
        ->where('products.data.0.name', 'Community Product'));
});

test('marketplace page does not show unapproved products', function (): void {
    $seller = User::factory()->create();

    Product::factory()
        ->product()
        ->pending()
        ->visible()
        ->active()
        ->create(['seller_id' => $seller->id]);

    $response = $this->get('/store/marketplace');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/marketplace/index')
        ->has('products.data', 0));
});

test('marketplace page does not show inactive products', function (): void {
    $seller = User::factory()->create();

    Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->inactive()
        ->create(['seller_id' => $seller->id]);

    $response = $this->get('/store/marketplace');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/marketplace/index')
        ->has('products.data', 0));
});

test('marketplace page does not show hidden products', function (): void {
    $seller = User::factory()->create();

    Product::factory()
        ->product()
        ->approved()
        ->hidden()
        ->active()
        ->create(['seller_id' => $seller->id]);

    $response = $this->get('/store/marketplace');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/marketplace/index')
        ->has('products.data', 0));
});

test('marketplace page does not show subscription only products', function (): void {
    $seller = User::factory()->create();

    Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['seller_id' => $seller->id, 'is_subscription_only' => true]);

    $response = $this->get('/store/marketplace');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/marketplace/index')
        ->has('products.data', 0));
});

test('marketplace page handles empty state', function (): void {
    $response = $this->get('/store/marketplace');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/marketplace/index')
        ->has('products.data', 0));
});
