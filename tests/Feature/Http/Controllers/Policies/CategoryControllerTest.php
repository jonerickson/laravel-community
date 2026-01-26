<?php

declare(strict_types=1);

use App\Models\Policy;
use App\Models\PolicyCategory;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Policy Category Controller Tests
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Index Tests
|--------------------------------------------------------------------------
*/

it('can view policy categories index as guest', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->get(route('policies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/index')
        ->has('categories')
    );
});

it('can view policy categories index as authenticated user', function (): void {
    $user = User::factory()->create();
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('policies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/index')
        ->has('categories')
    );
});

it('displays active categories with active policies', function (): void {
    $category = PolicyCategory::factory()->create([
        'name' => 'Legal',
        'is_active' => true,
        'order' => 1,
    ]);
    Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->get(route('policies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/index')
        ->has('categories', 1)
        ->where('categories.0.name', 'Legal')
    );
});

it('filters out inactive categories', function (): void {
    PolicyCategory::factory()->create([
        'name' => 'Active Category',
        'is_active' => true,
        'order' => 1,
    ]);
    PolicyCategory::factory()->create([
        'name' => 'Inactive Category',
        'is_active' => false,
        'order' => 2,
    ]);

    $response = $this->get(route('policies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/index')
        ->has('categories', 1)
        ->where('categories.0.name', 'Active Category')
    );
});

it('orders categories by order field', function (): void {
    PolicyCategory::factory()->create([
        'name' => 'Second',
        'is_active' => true,
        'order' => 2,
    ]);
    PolicyCategory::factory()->create([
        'name' => 'First',
        'is_active' => true,
        'order' => 1,
    ]);

    $response = $this->get(route('policies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/index')
        ->has('categories', 2)
        ->where('categories.0.name', 'First')
        ->where('categories.1.name', 'Second')
    );
});

it('loads active and effective policies for each category', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);

    Policy::factory()->create([
        'title' => 'Active Policy',
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
        'order' => 1,
    ]);
    Policy::factory()->create([
        'title' => 'Inactive Policy',
        'policy_category_id' => $category->id,
        'is_active' => false,
        'effective_at' => now()->subDay(),
        'order' => 2,
    ]);
    Policy::factory()->create([
        'title' => 'Future Policy',
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->addDay(),
        'order' => 3,
    ]);

    $response = $this->get(route('policies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/index')
        ->has('categories', 1)
        ->has('categories.0.activePolicies', 1)
        ->where('categories.0.activePolicies.0.title', 'Active Policy')
    );
});

it('handles empty categories gracefully', function (): void {
    $response = $this->get(route('policies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/index')
        ->has('categories', 0)
    );
});

/*
|--------------------------------------------------------------------------
| Show Tests
|--------------------------------------------------------------------------
*/

it('can view single category as guest', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->get(route('policies.categories.show', $category->slug));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/show')
        ->has('category')
        ->has('policies')
        ->where('category.id', $category->id)
    );
});

it('can view single category as authenticated user', function (): void {
    $user = User::factory()->create();
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('policies.categories.show', $category->slug));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/show')
        ->has('category')
        ->has('policies')
    );
});

it('returns 403 for inactive category', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => false,
    ]);

    $response = $this->get(route('policies.categories.show', $category->slug));

    $response->assertForbidden();
});

it('returns 404 for non-existent category', function (): void {
    $response = $this->get(route('policies.categories.show', 'non-existent-category'));

    $response->assertNotFound();
});

it('displays active and effective policies in category show', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    Policy::factory()->create([
        'title' => 'Active Policy',
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
        'order' => 1,
    ]);

    $response = $this->get(route('policies.categories.show', $category->slug));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/show')
        ->has('policies', 1)
        ->where('policies.0.title', 'Active Policy')
    );
});

it('filters inactive policies in category show', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    Policy::factory()->create([
        'title' => 'Active Policy',
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
        'order' => 1,
    ]);
    Policy::factory()->create([
        'title' => 'Inactive Policy',
        'policy_category_id' => $category->id,
        'is_active' => false,
        'effective_at' => now()->subDay(),
        'order' => 2,
    ]);

    $response = $this->get(route('policies.categories.show', $category->slug));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/show')
        ->has('policies', 1)
        ->where('policies.0.title', 'Active Policy')
    );
});

it('filters future effective date policies in category show', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    Policy::factory()->create([
        'title' => 'Current Policy',
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
        'order' => 1,
    ]);
    Policy::factory()->create([
        'title' => 'Future Policy',
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->addDay(),
        'order' => 2,
    ]);

    $response = $this->get(route('policies.categories.show', $category->slug));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/show')
        ->has('policies', 1)
        ->where('policies.0.title', 'Current Policy')
    );
});

it('orders policies by order field in category show', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    Policy::factory()->create([
        'title' => 'Second Policy',
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
        'order' => 2,
    ]);
    Policy::factory()->create([
        'title' => 'First Policy',
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
        'order' => 1,
    ]);

    $response = $this->get(route('policies.categories.show', $category->slug));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/show')
        ->has('policies', 2)
        ->where('policies.0.title', 'First Policy')
        ->where('policies.1.title', 'Second Policy')
    );
});

it('displays category details in show', function (): void {
    $category = PolicyCategory::factory()->create([
        'name' => 'Privacy Policies',
        'description' => 'All privacy related policies',
        'is_active' => true,
    ]);

    $response = $this->get(route('policies.categories.show', $category->slug));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/show')
        ->has('category')
        ->where('category.name', 'Privacy Policies')
        ->where('category.description', 'All privacy related policies')
    );
});

it('handles category with no policies gracefully', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->get(route('policies.categories.show', $category->slug));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/categories/show')
        ->has('policies', 0)
    );
});
