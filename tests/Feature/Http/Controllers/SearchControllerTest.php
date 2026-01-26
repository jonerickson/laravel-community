<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Search Page Tests
|--------------------------------------------------------------------------
|
| Note: The database Scout driver has limitations with toSearchableArray()
| columns that don't exist in the database table (e.g., topic, forum,
| category, author on posts). Tests focus on search controller behavior
| and user search which works with the database driver.
|
*/

it('can view search page as guest', function (): void {
    $response = $this->get(route('search'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('search'));
});

it('can view search page as authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('search'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('search'));
});

it('returns empty results for empty query', function (): void {
    $response = $this->get(route('search', ['q' => '']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('query', '')
        ->where('results.data', [])
    );
});

it('returns empty results for query less than 2 characters', function (): void {
    $response = $this->get(route('search', ['q' => 'a']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('results.data', [])
    );
});

it('returns default filter values', function (): void {
    $response = $this->get(route('search'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->has('filters')
        ->where('filters.types', ['policy', 'post', 'product', 'topic', 'user'])
        ->where('filters.sort_by', 'relevance')
        ->where('filters.sort_order', 'desc')
        ->where('filters.per_page', 20)
    );
});

it('accepts custom types filter as array', function (): void {
    $response = $this->get(route('search', ['types' => ['user']]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.types', ['user'])
    );
});

it('accepts custom types filter as string', function (): void {
    $response = $this->get(route('search', ['types' => 'user']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.types', ['user'])
    );
});

it('defaults to all types when invalid types provided', function (): void {
    $response = $this->get(route('search', ['types' => ['invalid']]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.types', ['policy', 'post', 'product', 'topic', 'user'])
    );
});

it('accepts sort_by parameter', function (): void {
    $response = $this->get(route('search', ['sort_by' => 'created_at']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.sort_by', 'created_at')
    );
});

it('accepts sort_order parameter', function (): void {
    $response = $this->get(route('search', ['sort_order' => 'asc']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.sort_order', 'asc')
    );
});

it('accepts per_page parameter with max limit of 50', function (): void {
    $response = $this->get(route('search', ['per_page' => 100]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.per_page', 50)
    );
});

it('returns counts for each type', function (): void {
    $response = $this->get(route('search'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->has('counts')
        ->has('counts.topics')
        ->has('counts.posts')
        ->has('counts.policies')
        ->has('counts.products')
        ->has('counts.users')
    );
});

it('searches users by name', function (): void {
    User::factory()->create(['name' => 'John Smith']);
    User::factory()->create(['name' => 'Jane Doe']);

    $response = $this->get(route('search', ['q' => 'John', 'types' => ['user']]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('counts.users', 1)
    );
});

it('searches users and returns matching results', function (): void {
    User::factory()->create(['name' => 'Searchable Test User']);

    $response = $this->get(route('search', ['q' => 'Searchable', 'types' => ['user']]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('query', 'Searchable')
        ->has('results.data', 1)
        ->where('results.data.0.type', 'user')
        ->where('results.data.0.title', 'Searchable Test User')
    );
});

it('accepts created_after date filter', function (): void {
    $response = $this->get(route('search', ['created_after' => '2024-01-01']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->has('filters.created_after')
    );
});

it('accepts created_before date filter', function (): void {
    $response = $this->get(route('search', ['created_before' => '2024-12-31']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->has('filters.created_before')
    );
});

it('accepts updated_after date filter', function (): void {
    $response = $this->get(route('search', ['updated_after' => '2024-01-01']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->has('filters.updated_after')
    );
});

it('accepts updated_before date filter', function (): void {
    $response = $this->get(route('search', ['updated_before' => '2024-12-31']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->has('filters.updated_before')
    );
});

it('accepts page parameter for pagination', function (): void {
    $response = $this->get(route('search', ['page' => 2]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->has('results')
    );
});

it('paginates results correctly', function (): void {
    // Create many users to test pagination
    User::factory()->count(25)->create(['name' => fn (): string => 'TestUser '.fake()->randomNumber(5)]);

    $response = $this->get(route('search', ['q' => 'TestUser', 'types' => ['user'], 'per_page' => 10]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->has('results')
        ->where('filters.per_page', 10)
    );
});

it('handles null query parameter gracefully', function (): void {
    $response = $this->get(route('search'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('query', '')
    );
});

it('filters users by created_after date', function (): void {
    User::factory()->create([
        'name' => 'OldUser Test',
        'created_at' => now()->subDays(10),
    ]);

    User::factory()->create([
        'name' => 'NewUser Test',
        'created_at' => now(),
    ]);

    $response = $this->get(route('search', [
        'q' => 'User Test',
        'types' => ['user'],
        'created_after' => now()->subDays(5)->toDateString(),
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('counts.users', 1)
    );
});

it('filters users by created_before date', function (): void {
    User::factory()->create([
        'name' => 'OldUserXY Test',
        'created_at' => now()->subDays(10),
    ]);

    User::factory()->create([
        'name' => 'NewUserXY Test',
        'created_at' => now(),
    ]);

    $response = $this->get(route('search', [
        'q' => 'UserXY',
        'types' => ['user'],
        'created_before' => now()->subDays(5)->toDateString(),
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('counts.users', 1)
    );
});

it('sorts user results by created_at ascending', function (): void {
    User::factory()->create([
        'name' => 'UserSort First',
        'created_at' => now()->subDays(10),
    ]);

    User::factory()->create([
        'name' => 'UserSort Second',
        'created_at' => now(),
    ]);

    $response = $this->get(route('search', [
        'q' => 'UserSort',
        'types' => ['user'],
        'sort_by' => 'created_at',
        'sort_order' => 'asc',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('results.data.0.title', 'UserSort First')
        ->where('results.data.1.title', 'UserSort Second')
    );
});

it('sorts user results by created_at descending', function (): void {
    User::factory()->create([
        'name' => 'UserSortDesc First',
        'created_at' => now()->subDays(10),
    ]);

    User::factory()->create([
        'name' => 'UserSortDesc Second',
        'created_at' => now(),
    ]);

    $response = $this->get(route('search', [
        'q' => 'UserSortDesc',
        'types' => ['user'],
        'sort_by' => 'created_at',
        'sort_order' => 'desc',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('results.data.0.title', 'UserSortDesc Second')
        ->where('results.data.1.title', 'UserSortDesc First')
    );
});

it('sorts user results by title', function (): void {
    User::factory()->create(['name' => 'ZuserTitle Alpha']);
    User::factory()->create(['name' => 'AuserTitle Beta']);

    $response = $this->get(route('search', [
        'q' => 'userTitle',
        'types' => ['user'],
        'sort_by' => 'title',
        'sort_order' => 'asc',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('results.data.0.title', 'AuserTitle Beta')
        ->where('results.data.1.title', 'ZuserTitle Alpha')
    );
});

it('returns user url in search results', function (): void {
    $user = User::factory()->create(['name' => 'UserWithUrl Test']);

    $response = $this->get(route('search', ['q' => 'UserWithUrl', 'types' => ['user']]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('results.data.0.url', route('users.show', $user->reference_id))
    );
});

it('returns user groups as description in search results', function (): void {
    $user = User::factory()->create(['name' => 'UserWithGroups Test']);
    $group = Group::factory()->create([
        'name' => 'VIP Members',
        'is_active' => true,
        'is_visible' => true,
    ]);
    $user->groups()->attach($group);

    $response = $this->get(route('search', ['q' => 'UserWithGroups', 'types' => ['user']]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('results.data.0.description', fn ($desc): bool => str_contains((string) $desc, 'VIP Members'))
    );
});

it('handles search with only user type selected', function (): void {
    User::factory()->create(['name' => 'OnlyUserType Test']);

    $response = $this->get(route('search', ['q' => 'OnlyUserType', 'types' => ['user']]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('counts.users', 1)
        ->where('counts.posts', 0)
        ->where('counts.products', 0)
        ->where('counts.topics', 0)
        ->where('counts.policies', 0)
    );
});

it('filters by multiple valid types', function (): void {
    $response = $this->get(route('search', ['types' => ['user', 'product']]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.types', ['user', 'product'])
    );
});

it('filters out invalid types from array', function (): void {
    $response = $this->get(route('search', ['types' => ['user', 'invalid', 'product']]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.types', ['user', 'product'])
    );
});

it('handles comma-separated types string', function (): void {
    $response = $this->get(route('search', ['types' => 'user,product']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.types', ['user', 'product'])
    );
});

it('respects minimum per_page value', function (): void {
    $response = $this->get(route('search', ['per_page' => 1]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('search')
        ->where('filters.per_page', 1)
    );
});
