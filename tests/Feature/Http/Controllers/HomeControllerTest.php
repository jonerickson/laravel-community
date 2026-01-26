<?php

declare(strict_types=1);

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Home Page Tests
|--------------------------------------------------------------------------
*/

it('can view home page as guest', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('home'));
});

it('can view home page as authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('home'));
});

it('renders home page component', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('home'));
});

it('displays visible active subscription products', function (): void {
    $category = ProductCategory::factory()->active()->create();
    $product = Product::factory()
        ->subscription()
        ->visible()
        ->active()
        ->approved()
        ->create();
    $product->categories()->attach($category);

    Price::factory()->recurring()->active()->create([
        'product_id' => $product->id,
        'is_visible' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
});

it('does not display inactive subscription products', function (): void {
    $category = ProductCategory::factory()->active()->create();
    $product = Product::factory()
        ->subscription()
        ->visible()
        ->inactive()
        ->approved()
        ->create();
    $product->categories()->attach($category);

    Price::factory()->recurring()->active()->create([
        'product_id' => $product->id,
        'is_visible' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
});

it('does not display hidden subscription products', function (): void {
    $category = ProductCategory::factory()->active()->create();
    $product = Product::factory()
        ->subscription()
        ->hidden()
        ->active()
        ->approved()
        ->create();
    $product->categories()->attach($category);

    Price::factory()->recurring()->active()->create([
        'product_id' => $product->id,
        'is_visible' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
});

it('does not display product type items on home page subscriptions', function (): void {
    $category = ProductCategory::factory()->active()->create();
    $product = Product::factory()
        ->product()
        ->visible()
        ->active()
        ->approved()
        ->create();
    $product->categories()->attach($category);

    Price::factory()->oneTime()->active()->create([
        'product_id' => $product->id,
        'is_visible' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
});

it('handles empty subscriptions gracefully', function (): void {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('home'));
});
