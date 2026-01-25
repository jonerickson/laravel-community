<?php

declare(strict_types=1);

use App\Models\Policy;
use App\Models\PolicyCategory;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Policy Controller Tests
|--------------------------------------------------------------------------
*/

it('can view policy as guest', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    $policy = Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->get(route('policies.show', [$category->slug, $policy->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/show')
        ->has('category')
        ->has('policy')
        ->where('policy.id', $policy->id)
        ->where('policy.title', $policy->title)
    );
});

it('can view policy as authenticated user', function (): void {
    $user = User::factory()->create();
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    $policy = Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->get(route('policies.show', [$category->slug, $policy->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/show')
        ->has('category')
        ->has('policy')
        ->where('policy.id', $policy->id)
    );
});

it('returns 403 for inactive policy', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    $policy = Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => false,
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->get(route('policies.show', [$category->slug, $policy->slug]));

    $response->assertForbidden();
});

it('returns 403 for policy in inactive category', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => false,
    ]);
    $policy = Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->get(route('policies.show', [$category->slug, $policy->slug]));

    $response->assertForbidden();
});

it('returns 403 for future effective date policy', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    $policy = Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->addDay(),
    ]);

    $response = $this->get(route('policies.show', [$category->slug, $policy->slug]));

    $response->assertForbidden();
});

it('can view policy with null effective date', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    $policy = Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => null,
    ]);

    $response = $this->get(route('policies.show', [$category->slug, $policy->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/show')
        ->has('policy')
    );
});

it('returns 404 for non-existent policy', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->get(route('policies.show', [$category->slug, 'non-existent-policy']));

    $response->assertNotFound();
});

it('returns 404 for non-existent category', function (): void {
    $response = $this->get(route('policies.show', ['non-existent-category', 'some-policy']));

    $response->assertNotFound();
});

it('displays policy content', function (): void {
    $category = PolicyCategory::factory()->create([
        'is_active' => true,
    ]);
    $policy = Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
        'content' => 'This is the policy content.',
        'version' => 'v1.0.0',
    ]);

    $response = $this->get(route('policies.show', [$category->slug, $policy->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/show')
        ->has('policy')
        ->where('policy.content', 'This is the policy content.')
        ->where('policy.version', 'v1.0.0')
    );
});

it('displays category data with policy', function (): void {
    $category = PolicyCategory::factory()->create([
        'name' => 'Legal Policies',
        'description' => 'All legal policies',
        'is_active' => true,
    ]);
    $policy = Policy::factory()->create([
        'policy_category_id' => $category->id,
        'is_active' => true,
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->get(route('policies.show', [$category->slug, $policy->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($inertia) => $inertia
        ->component('policies/show')
        ->has('category')
        ->where('category.name', 'Legal Policies')
        ->where('category.description', 'All legal policies')
    );
});
