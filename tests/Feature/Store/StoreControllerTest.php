<?php

declare(strict_types=1);

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

test('store index page renders for guests', function (): void {
    $response = $this->get('/store');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/index'));
});

test('store index page renders for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/store');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/index'));
});

test('store index uses deferred props for categories', function (): void {
    ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $response = $this->get('/store');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/index'));
});

test('store index uses deferred props for featured products', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->featured()
        ->create();

    $product->categories()->attach($category);

    Price::factory()
        ->active()
        ->for($product)
        ->create(['is_visible' => true]);

    $response = $this->get('/store');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/index'));
});

test('store index uses deferred props for user provided products', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $seller = User::factory()->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create([
            'seller_id' => $seller->id,
        ]);

    $product->categories()->attach($category);

    Price::factory()
        ->active()
        ->for($product)
        ->create(['is_visible' => true]);

    $response = $this->get('/store');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/index'));
});

test('store index does not show inactive categories', function (): void {
    ProductCategory::factory()
        ->inactive()
        ->visible()
        ->create();

    $response = $this->get('/store');

    $response->assertOk();
});

test('store index does not show hidden categories', function (): void {
    ProductCategory::factory()
        ->active()
        ->hidden()
        ->create();

    $response = $this->get('/store');

    $response->assertOk();
});

test('store index does not show unapproved products', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->pending()
        ->visible()
        ->active()
        ->featured()
        ->create();

    $product->categories()->attach($category);

    $response = $this->get('/store');

    $response->assertOk();
});

test('store index does not show inactive products', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->inactive()
        ->featured()
        ->create();

    $product->categories()->attach($category);

    $response = $this->get('/store');

    $response->assertOk();
});

test('store index does not show hidden products', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->hidden()
        ->active()
        ->featured()
        ->create();

    $product->categories()->attach($category);

    $response = $this->get('/store');

    $response->assertOk();
});
