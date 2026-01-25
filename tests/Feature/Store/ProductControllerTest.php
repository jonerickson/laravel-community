<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

test('product show page renders for guests', function (): void {
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

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/products/show'));
});

test('product show page renders for authenticated users', function (): void {
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

    $response = $this->actingAs($user)->get('/store/products/'.$product->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('store/products/show'));
});

test('product show page displays product data', function (): void {
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

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/products/show')
        ->where('product.name', 'Test Product'));
});

test('product show page shows approved reviews', function (): void {
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

    $reviewer = User::factory()->create();

    Comment::factory()
        ->approved()
        ->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'rating' => 5,
            'content' => 'Great product!',
            'created_by' => $reviewer->id,
        ]);

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/products/show')
        ->has('reviews.data', 1));
});

test('product show page does not show unapproved reviews', function (): void {
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

    $reviewer = User::factory()->create();

    // Create review and then unapprove it (model event auto-approves during creation)
    $review = Comment::factory()
        ->approved()
        ->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'rating' => 3,
            'content' => 'Pending review',
            'created_by' => $reviewer->id,
        ]);
    $review->update(['is_approved' => false]);

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/products/show')
        ->has('reviews.data', 0));
});

test('product show page shows reviews with replies', function (): void {
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

    $reviewer = User::factory()->create();

    $review = Comment::factory()
        ->approved()
        ->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'rating' => 4,
            'content' => 'Good product',
            'created_by' => $reviewer->id,
        ]);

    Comment::factory()
        ->approved()
        ->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'parent_id' => $review->id,
            'content' => 'Thank you for your review!',
            'created_by' => $reviewer->id,
        ]);

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/products/show')
        ->has('reviews.data', 1));
});

test('product show page returns 404 for non-existent product', function (): void {
    $response = $this->get('/store/products/non-existent-product');

    $response->assertNotFound();
});

test('product show page returns 403 for inactive product', function (): void {
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

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertForbidden();
});

test('product show page returns 403 for unapproved product', function (): void {
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

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertForbidden();
});

test('product show page returns 403 for product in inactive category', function (): void {
    $category = ProductCategory::factory()
        ->inactive()
        ->visible()
        ->create();

    $product = Product::factory()
        ->product()
        ->approved()
        ->visible()
        ->active()
        ->create();

    $product->categories()->attach($category);

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertForbidden();
});

test('product show page loads prices for display', function (): void {
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

    Price::factory()
        ->active()
        ->default()
        ->for($product)
        ->create([
            'name' => 'Standard',
            'is_visible' => true,
        ]);

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/products/show')
        ->has('product.prices', 1));
});

test('product show page does not show hidden prices', function (): void {
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

    Price::factory()
        ->active()
        ->for($product)
        ->create([
            'is_visible' => false,
        ]);

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/products/show')
        ->has('product.prices', 0));
});

test('product show page does not show inactive prices', function (): void {
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

    Price::factory()
        ->inactive()
        ->for($product)
        ->create([
            'is_visible' => true,
        ]);

    $response = $this->get('/store/products/'.$product->slug);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('store/products/show')
        ->has('product.prices', 0));
});

test('product store adds item to cart', function (): void {
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

    $response = $this->actingAs($user)->post('/store/products/'.$product->slug, [
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'The item was successfully added to your shopping cart.');
});

test('product store requires authentication', function (): void {
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

    $response = $this->post('/store/products/'.$product->slug, [
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $response->assertRedirect(route('login'));
});

test('product store validates price_id is required', function (): void {
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

    $response = $this->actingAs($user)->post('/store/products/'.$product->slug, [
        'quantity' => 1,
    ]);

    $response->assertSessionHasErrors(['price_id' => 'Please select a price option.']);
});

test('product store validates price_id exists', function (): void {
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

    $response = $this->actingAs($user)->post('/store/products/'.$product->slug, [
        'price_id' => 99999,
        'quantity' => 1,
    ]);

    $response->assertSessionHasErrors(['price_id' => 'The selected price is invalid.']);
});

test('product store validates quantity minimum', function (): void {
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

    $response = $this->actingAs($user)->post('/store/products/'.$product->slug, [
        'price_id' => $price->id,
        'quantity' => 0,
    ]);

    $response->assertSessionHasErrors(['quantity' => 'The quantity must be at least 1.']);
});

test('product store validates quantity maximum', function (): void {
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

    $response = $this->actingAs($user)->post('/store/products/'.$product->slug, [
        'price_id' => $price->id,
        'quantity' => 100,
    ]);

    $response->assertSessionHasErrors(['quantity' => 'The quantity cannot exceed 99.']);
});

test('product store returns 403 for inactive product', function (): void {
    $user = User::factory()->create();

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

    $price = Price::factory()
        ->active()
        ->default()
        ->for($product)
        ->create(['is_visible' => true]);

    $response = $this->actingAs($user)->post('/store/products/'.$product->slug, [
        'price_id' => $price->id,
        'quantity' => 1,
    ]);

    $response->assertForbidden();
});
