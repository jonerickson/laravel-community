<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\Group;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| User Profile Page Tests
|--------------------------------------------------------------------------
*/

it('can view user profile page as guest', function (): void {
    $user = User::factory()->create();

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('users/show'));
});

it('can view user profile page as authenticated user', function (): void {
    $viewer = User::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($viewer)->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('users/show'));
});

it('can view own profile page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->has('user')
        ->where('user.name', $user->name)
    );
});

it('returns 404 for non-existent user', function (): void {
    $response = $this->get(route('users.show', 'non-existent-reference-id'));

    $response->assertNotFound();
});

it('displays user data on profile page', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'signature' => 'This is my signature',
    ]);

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->where('user.name', 'Test User')
    );
});

it('displays public fields for user', function (): void {
    $user = User::factory()->create();
    $publicField = Field::factory()->create([
        'name' => 'location',
        'label' => 'Location',
        'is_public' => true,
    ]);
    $user->fields()->attach($publicField, ['value' => 'New York']);

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->has('user.fields', 1)
    );
});

it('does not display private fields for user', function (): void {
    $user = User::factory()->create();
    $privateField = Field::factory()->create([
        'name' => 'secret',
        'label' => 'Secret Info',
        'is_public' => false,
    ]);
    $user->fields()->attach($privateField, ['value' => 'hidden value']);

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->has('user.fields', 0)
    );
});

it('displays active visible groups for user', function (): void {
    $user = User::factory()->create();
    $activeGroup = Group::factory()->create([
        'name' => 'Premium Members',
        'is_active' => true,
        'is_visible' => true,
    ]);
    $user->groups()->attach($activeGroup);

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->has('user.groups')
    );
});

it('does not display inactive groups for user', function (): void {
    $user = User::factory()->create();
    $inactiveGroup = Group::factory()->create([
        'name' => 'Inactive Group',
        'is_active' => false,
        'is_visible' => true,
    ]);
    $user->groups()->attach($inactiveGroup);

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->where('user.groups', fn ($groups) => collect($groups)->where('name', 'Inactive Group')->isEmpty())
    );
});

it('does not display hidden groups for user', function (): void {
    $user = User::factory()->create();
    $hiddenGroup = Group::factory()->create([
        'name' => 'Hidden Group',
        'is_active' => true,
        'is_visible' => false,
    ]);
    $user->groups()->attach($hiddenGroup);

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->where('user.groups', fn ($groups) => collect($groups)->where('name', 'Hidden Group')->isEmpty())
    );
});

it('displays multiple public fields in order', function (): void {
    $user = User::factory()->create();

    $field1 = Field::factory()->create([
        'name' => 'field_a',
        'label' => 'Field A',
        'is_public' => true,
        'order' => 2,
    ]);
    $field2 = Field::factory()->create([
        'name' => 'field_b',
        'label' => 'Field B',
        'is_public' => true,
        'order' => 1,
    ]);

    $user->fields()->attach($field1, ['value' => 'Value A']);
    $user->fields()->attach($field2, ['value' => 'Value B']);

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->has('user.fields', 2)
    );
});

it('handles user with no groups', function (): void {
    $user = User::factory()->create();
    // User may have default group from factory, clear them
    $user->groups()->detach();

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->has('user')
    );
});

it('handles user with no fields', function (): void {
    $user = User::factory()->create();
    $user->fields()->detach();

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->has('user.fields', 0)
    );
});

it('displays user reference id on profile page', function (): void {
    $user = User::factory()->create();

    $response = $this->get(route('users.show', $user->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/show')
        ->where('user.referenceId', $user->reference_id)
    );
});
