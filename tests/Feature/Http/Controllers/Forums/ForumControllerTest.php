<?php

declare(strict_types=1);

use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\Topic;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Forum Show Tests
|--------------------------------------------------------------------------
*/

it('can view forum page as guest with read permission', function (): void {
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

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($guestGroup, [
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

    $response = $this->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/show')
        ->has('forum')
        ->where('forum.id', $forum->id)
        ->where('forum.name', $forum->name)
    );
});

it('can view forum page as authenticated user with read permission', function (): void {
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

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/show')
        ->has('forum')
        ->where('forum.id', $forum->id)
    );
});

it('cannot view inactive forum', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create([
        'is_active' => false,
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

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertForbidden();
});

it('cannot view forum without read permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    $forum->groups()->attach($group, [
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

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertForbidden();
});

it('cannot view forum when category is inactive', function (): void {
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

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
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

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertForbidden();
});

it('cannot view forum when category lacks read permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false,
        'read' => false, // No read permission on category
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

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertForbidden();
});

it('returns 404 for non-existent forum', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => 'non-existent-forum']));

    $response->assertNotFound();
});

it('displays forum with deferred children', function (): void {
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

    // Create child forum
    $childForum = Forum::factory()->create([
        'is_active' => true,
        'parent_id' => $forum->id,
        'category_id' => $category->id,
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

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/show')
        ->has('forum')
    );
});

it('displays forum with deferred topics', function (): void {
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

    // Create topics in the forum
    Topic::factory()->count(3)->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/show')
        ->has('forum')
    );
});

it('loads forum followers count', function (): void {
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

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/show')
        ->has('forum')
        ->has('forum.followersCount')
    );
});

it('can view forum without category', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => null,
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

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/show')
        ->has('forum')
        ->where('forum.id', $forum->id)
    );
});

it('admin can view any active forum', function (): void {
    $admin = User::factory()->asAdmin()->create();

    $forum = Forum::factory()->create(['is_active' => true]);

    $response = $this->actingAs($admin)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/show')
        ->has('forum')
    );
});

it('filters unauthorized child forums from view', function (): void {
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

    // Create accessible child forum
    $accessibleChild = Forum::factory()->create([
        'is_active' => true,
        'parent_id' => $forum->id,
        'category_id' => $category->id,
    ]);
    $accessibleChild->groups()->attach($group, [
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

    // Create inaccessible child forum (no permissions for user's group)
    $inaccessibleChild = Forum::factory()->create([
        'is_active' => true,
        'parent_id' => $forum->id,
        'category_id' => $category->id,
    ]);
    // No group attachment - user can't access

    $response = $this->actingAs($user)->get(route('forums.show', ['forum' => $forum->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('forums/show')
        ->has('forum')
    );
});
