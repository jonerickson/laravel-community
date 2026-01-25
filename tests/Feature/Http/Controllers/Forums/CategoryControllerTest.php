<?php

declare(strict_types=1);

use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Category Index Tests
|--------------------------------------------------------------------------
*/

it('can view forum categories index as guest with read permission', function (): void {
    $guestGroup = Group::factory()->asDefaultGuest()->create(['is_active' => true]);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($guestGroup, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->get(route('forums.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/index')
        ->has('categories')
    );
});

it('can view forum categories index as authenticated user', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/index')
        ->has('categories')
    );
});

it('filters out inactive categories from index', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $activeCategory = ForumCategory::factory()->create(['is_active' => true]);
    $activeCategory->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $inactiveCategory = ForumCategory::factory()->create(['is_active' => false]);
    $inactiveCategory->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/index')
        ->has('categories', 1)
    );
});

it('filters out categories without read permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $accessibleCategory = ForumCategory::factory()->create(['is_active' => true]);
    $accessibleCategory->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $inaccessibleCategory = ForumCategory::factory()->create(['is_active' => true]);
    $inaccessibleCategory->groups()->attach($group, [
        'create' => false,
        'read' => false, // No read permission
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/index')
        ->has('categories', 1)
    );
});

it('loads categories with forums', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/index')
        ->has('categories', 1)
    );
});

it('only shows root forums in category index', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $parentForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);
    $parentForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    // Child forum should not appear at root level
    $childForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => $parentForum->id,
    ]);
    $childForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/index')
        ->has('categories', 1)
    );
});

it('filters out inaccessible forums from categories', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $accessibleForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);
    $accessibleForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    // Inaccessible forum - no group permissions
    $inaccessibleForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);

    $response = $this->actingAs($user)->get(route('forums.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/index')
    );
});

it('orders categories by order field', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $secondCategory = ForumCategory::factory()->create(['is_active' => true, 'order' => 2]);
    $secondCategory->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $firstCategory = ForumCategory::factory()->create(['is_active' => true, 'order' => 1]);
    $firstCategory->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/index')
        ->has('categories', 2)
    );
});

/*
|--------------------------------------------------------------------------
| Category Show Tests
|--------------------------------------------------------------------------
*/

it('can view single category as guest with read permission', function (): void {
    $guestGroup = Group::factory()->asDefaultGuest()->create(['is_active' => true]);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($guestGroup, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/show')
        ->has('category')
        ->where('category.id', $category->id)
        ->where('category.name', $category->name)
    );
});

it('can view single category as authenticated user', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/show')
        ->has('category')
        ->where('category.id', $category->id)
    );
});

it('cannot view inactive category', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => false]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertForbidden();
});

it('cannot view category without read permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => false,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertForbidden();
});

it('returns 404 for non-existent category', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('forums.categories.show', ['category' => 'non-existent-category']));

    $response->assertNotFound();
});

it('shows forums in category show page with deferred loading', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/show')
        ->has('category')
    );
});

it('only shows root forums in category show page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $parentForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);
    $parentForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    // Child forum should not appear at root level
    $childForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => $parentForum->id,
    ]);
    $childForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/show')
        ->has('category')
    );
});

it('filters out inactive forums in category show page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $activeForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);
    $activeForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    // Inactive forum should be filtered out
    $inactiveForum = Forum::factory()->create([
        'is_active' => false,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);
    $inactiveForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/show')
    );
});

it('filters out inaccessible forums in category show page', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $accessibleForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);
    $accessibleForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    // Inaccessible forum - no group permissions
    $inaccessibleForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
    ]);

    $response = $this->actingAs($user)->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/show')
    );
});

it('admin can view any active category', function (): void {
    $admin = User::factory()->asAdmin()->create();

    $category = ForumCategory::factory()->create(['is_active' => true]);

    $response = $this->actingAs($admin)->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/show')
        ->has('category')
    );
});

it('orders forums in category show page by order field', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $secondForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
        'order' => 2,
    ]);
    $secondForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $firstForum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
        'parent_id' => null,
        'order' => 1,
    ]);
    $firstForum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $response = $this->actingAs($user)->get(route('forums.categories.show', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/categories/show')
        ->has('category')
    );
});
