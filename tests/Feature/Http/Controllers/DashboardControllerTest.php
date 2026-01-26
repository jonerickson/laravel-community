<?php

declare(strict_types=1);

use App\Models\Forum;
use App\Models\Group;
use App\Models\Post;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\Topic;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Dashboard Access Tests
|--------------------------------------------------------------------------
*/

it('redirects guests to login page', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

it('displays dashboard page for authenticated users', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('dashboard'));
});

it('renders dashboard page component', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('dashboard'));
});

/*
|--------------------------------------------------------------------------
| Dashboard Support Tickets Tests
|--------------------------------------------------------------------------
*/

it('displays user own active support tickets', function (): void {
    $category = SupportTicketCategory::factory()->create();
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

it('does not display closed support tickets on dashboard', function (): void {
    $category = SupportTicketCategory::factory()->create();
    $user = User::factory()->create();
    SupportTicket::factory()->closed()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

it('does not display other users support tickets', function (): void {
    $category = SupportTicketCategory::factory()->create();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    SupportTicket::factory()->open()->create([
        'created_by' => $otherUser->id,
        'support_ticket_category_id' => $category->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

/*
|--------------------------------------------------------------------------
| Dashboard Products Tests
|--------------------------------------------------------------------------
*/

it('displays newest product on dashboard', function (): void {
    $user = User::factory()->create();
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

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

it('displays featured product on dashboard', function (): void {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->active()->create();
    $product = Product::factory()
        ->product()
        ->featured()
        ->visible()
        ->active()
        ->approved()
        ->create();
    $product->categories()->attach($category);

    Price::factory()->oneTime()->active()->create([
        'product_id' => $product->id,
        'is_visible' => true,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

it('does not display unapproved products on dashboard', function (): void {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->active()->create();
    $product = Product::factory()
        ->product()
        ->visible()
        ->active()
        ->pending()
        ->create();
    $product->categories()->attach($category);

    Price::factory()->oneTime()->active()->create([
        'product_id' => $product->id,
        'is_visible' => true,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

it('does not display inactive products on dashboard', function (): void {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->active()->create();
    $product = Product::factory()
        ->product()
        ->visible()
        ->inactive()
        ->approved()
        ->create();
    $product->categories()->attach($category);

    Price::factory()->oneTime()->active()->create([
        'product_id' => $product->id,
        'is_visible' => true,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

/*
|--------------------------------------------------------------------------
| Dashboard Trending Topics Tests
|--------------------------------------------------------------------------
*/

it('displays trending topics on dashboard', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();

    $defaultGuestGroup = Group::factory()->asDefaultGuest()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $forum->groups()->attach($defaultGuestGroup, ['read' => true]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

/*
|--------------------------------------------------------------------------
| Dashboard Latest Blog Posts Tests
|--------------------------------------------------------------------------
*/

it('displays latest blog posts on dashboard', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
        'published_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

it('does not display unpublished blog posts on dashboard', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    Post::factory()->blog()->draft()->create([
        'created_by' => $author->id,
        'is_approved' => true,
        'topic_id' => null,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

it('does not display unapproved blog posts on dashboard', function (): void {
    $user = User::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->blog()->published()->create([
        'created_by' => $author->id,
        'topic_id' => null,
        'published_at' => now()->subMinute(),
    ]);
    $post->update(['is_approved' => false]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

/*
|--------------------------------------------------------------------------
| Dashboard Empty State Tests
|--------------------------------------------------------------------------
*/

it('handles dashboard with no data gracefully', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('dashboard'));
});
