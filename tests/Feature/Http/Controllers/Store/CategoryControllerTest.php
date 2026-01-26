<?php

declare(strict_types=1);

use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

test('categories index page renders for guests', function (): void {
    $response = $this->get('/store/categories');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/categories/index'));
});

test('categories index page renders for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/store/categories');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/categories/index'));
});

test('categories index shows active and visible categories', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create(['name' => 'Test Category']);

    $response = $this->get('/store/categories');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/index')
        ->has('categories', 1)
        ->where('categories.0.name', 'Test Category'));
});

test('categories index does not show inactive categories', function (): void {
    ProductCategory::factory()
        ->inactive()
        ->visible()
        ->create();

    $response = $this->get('/store/categories');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/index')
        ->has('categories', 0));
});

test('categories index does not show hidden categories', function (): void {
    ProductCategory::factory()
        ->active()
        ->hidden()
        ->create();

    $response = $this->get('/store/categories');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/index')
        ->has('categories', 0));
});

test('categories index does not show child categories at top level', function (): void {
    $parent = ProductCategory::factory()
        ->active()
        ->visible()
        ->create(['name' => 'Store Top Level Parent']);

    ProductCategory::factory()
        ->active()
        ->visible()
        ->create([
            'name' => 'Store Top Level Child',
            'parent_id' => $parent->id,
        ]);

    $response = $this->get('/store/categories');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/index')
        ->has('categories', 1)
        ->where('categories.0.name', 'Store Top Level Parent'));
});

test('category show page renders for guests', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $response = $this->get('/store/categories/'.$category->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/categories/show'));
});

test('category show page renders for authenticated users', function (): void {
    $user = User::factory()->create();
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $response = $this->actingAs($user)->get('/store/categories/'.$category->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/categories/show'));
});

test('category show page displays category data', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create(['name' => 'Test Category']);

    $response = $this->get('/store/categories/'.$category->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/show')
        ->where('category.name', 'Test Category'));
});

test('category show page shows products in category', function (): void {
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

    Price::factory()
        ->active()
        ->default()
        ->for($product)
        ->create(['is_visible' => true]);

    $response = $this->get('/store/categories/'.$category->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/show')
        ->has('products.data', 1));
});

test('category show page handles empty categories', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $response = $this->get('/store/categories/'.$category->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/show')
        ->has('products.data', 0));
});

test('category show page does not show unapproved products', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->pending()
        ->visible()
        ->active()
        ->create();

    $product->categories()->attach($category);

    $response = $this->get('/store/categories/'.$category->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/show')
        ->has('products.data', 0));
});

test('category show page does not show inactive products', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->inactive()
        ->create();

    $product->categories()->attach($category);

    $response = $this->get('/store/categories/'.$category->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/show')
        ->has('products.data', 0));
});

test('category show page does not show hidden products', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->hidden()
        ->active()
        ->create();

    $product->categories()->attach($category);

    $response = $this->get('/store/categories/'.$category->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/show')
        ->has('products.data', 0));
});

test('category show page does not show subscription only products', function (): void {
    $category = ProductCategory::factory()
        ->active()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create(['is_subscription_only' => true]);

    $product->categories()->attach($category);

    $response = $this->get('/store/categories/'.$category->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/show')
        ->has('products.data', 0));
});

test('category show page returns 403 for inactive category', function (): void {
    $category = ProductCategory::factory()
        ->inactive()
        ->visible()
        ->create();

    $response = $this->get('/store/categories/'.$category->slug);

    $response->assertForbidden();
});

test('category show page returns 404 for non-existent category', function (): void {
    $response = $this->get('/store/categories/non-existent-category');

    $response->assertNotFound();
});

test('category show page loads parent relationship', function (): void {
    $parent = ProductCategory::factory()
        ->active()
        ->visible()
        ->create(['name' => 'Store Parent Rel']);

    $child = ProductCategory::factory()
        ->active()
        ->visible()
        ->create([
            'name' => 'Store Child Rel',
            'parent_id' => $parent->id,
        ]);

    $response = $this->get('/store/categories/'.$child->fresh()->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/show')
        ->where('category.name', 'Store Child Rel'));
});

test('category show page loads children relationship', function (): void {
    $parent = ProductCategory::factory()
        ->active()
        ->visible()
        ->create(['name' => 'Store Parent Children']);

    ProductCategory::factory()
        ->active()
        ->visible()
        ->create([
            'name' => 'Store Child Children',
            'parent_id' => $parent->id,
        ]);

    $response = $this->get('/store/categories/'.$parent->fresh()->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/categories/show')
        ->where('category.name', 'Store Parent Children'));
});
