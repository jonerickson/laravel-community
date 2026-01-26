<?php

declare(strict_types=1);

use App\Enums\PostType;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    Group::factory()->asDefaultMemberGroup()->create();
});

function createForumWithPermissions(Group $group, array $overrides = [], ?ForumCategory $category = null): Forum
{
    $permissions = array_merge([
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
    ], $overrides);

    if ($category instanceof ForumCategory) {
        $category->groups()->attach($group, $permissions);
    }

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category?->id,
    ]);
    $forum->groups()->attach($group, $permissions);

    return $forum;
}

function createViewableTopic(Forum $forum, ?User $author = null): Topic
{
    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author?->id ?? User::factory()->create()->id,
    ]);

    Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $topic->created_by,
        'is_approved' => true,
        'is_published' => true,
        'published_at' => now()->subMinute(),
    ]);

    return $topic->refresh();
}

// viewAny

it('allows anyone to view any topics', function (): void {
    expect(Gate::forUser(null)->check('viewAny', Topic::class))->toBeTrue();

    $user = User::factory()->create();
    expect(Gate::forUser($user)->check('viewAny', Topic::class))->toBeTrue();
});

// view

it('allows guest to view topic in forum with guest group read permission', function (): void {
    $guestGroup = Group::factory()->asDefaultGuest()->create(['is_active' => true]);
    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($guestGroup, ['read' => true], $category);
    $topic = createViewableTopic($forum);

    expect(Gate::forUser(null)->check('view', $topic))->toBeTrue();
});

it('allows authenticated user to view topic with group read permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true], $category);
    $topic = createViewableTopic($forum);

    expect(Gate::forUser($user)->check('view', $topic))->toBeTrue();
});

it('denies viewing topic when forum has no read permission for user group', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => false], $category);
    $topic = createViewableTopic($forum);

    expect(Gate::forUser($user)->check('view', $topic))->toBeFalse();
});

it('denies viewing topic when forum is inactive', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $category->groups()->attach($group, [
        'create' => false, 'read' => true, 'update' => false, 'delete' => false,
        'moderate' => false, 'reply' => false, 'report' => false,
        'pin' => false, 'lock' => false, 'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => false,
        'category_id' => $category->id,
    ]);
    $forum->groups()->attach($group, [
        'create' => false, 'read' => true, 'update' => false, 'delete' => false,
        'moderate' => false, 'reply' => false, 'report' => false,
        'pin' => false, 'lock' => false, 'move' => false,
    ]);

    $topic = createViewableTopic($forum);

    expect(Gate::forUser($user)->check('view', $topic))->toBeFalse();
});

it('denies viewing topic when topic has no viewable posts', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true], $category);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    // Create a post that is not approved (not viewable by non-author, non-moderator)
    $otherUser = User::factory()->create();
    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $otherUser->id,
        'is_published' => true,
        'published_at' => now()->subMinute(),
    ]);
    $post->update(['is_approved' => false]);

    expect(Gate::forUser($user)->check('view', $topic))->toBeFalse();
});

it('allows viewing topic when post is unapproved but user is author', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true], $category);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    $post = Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $user->id,
        'is_published' => true,
        'published_at' => now()->subMinute(),
    ]);
    $post->update(['is_approved' => false]);

    expect(Gate::forUser($user)->check('view', $topic))->toBeTrue();
});

// create

it('denies guest from creating topic', function (): void {
    expect(Gate::forUser(null)->check('create', [Topic::class, null]))->toBeFalse();
});

it('allows authenticated user to create topic without forum', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->check('create', [Topic::class, null]))->toBeTrue();
});

it('allows authenticated user to create topic with forum create permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true, 'create' => true], $category);

    expect(Gate::forUser($user)->check('create', [Topic::class, $forum]))->toBeTrue();
});

it('denies creating topic when user has no create permission on forum', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true, 'create' => false], $category);

    expect(Gate::forUser($user)->check('create', [Topic::class, $forum]))->toBeFalse();
});

// update

it('denies guest from updating topic', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    expect(Gate::forUser(null)->check('update', $topic))->toBeFalse();
});

it('allows author to update their own topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true], $category);
    $topic = createViewableTopic($forum, $user);

    expect(Gate::forUser($user)->check('update', $topic))->toBeTrue();
});

it('denies author from updating locked topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true], $category);
    $topic = createViewableTopic($forum, $user);
    $topic->update(['is_locked' => true]);

    expect(Gate::forUser($user)->check('update', $topic))->toBeFalse();
});

it('allows user with forum update permission to update non-authored topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true, 'update' => true], $category);
    $topic = createViewableTopic($forum);

    expect(Gate::forUser($user)->check('update', $topic))->toBeTrue();
});

it('denies user without update permission from updating non-authored topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true, 'update' => false], $category);
    $topic = createViewableTopic($forum);

    expect(Gate::forUser($user)->check('update', $topic))->toBeFalse();
});

it('denies updating locked topic even with forum update permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true, 'update' => true], $category);
    $topic = createViewableTopic($forum);
    $topic->update(['is_locked' => true]);

    expect(Gate::forUser($user)->check('update', $topic))->toBeFalse();
});

// delete

it('denies guest from deleting topic', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    expect(Gate::forUser(null)->check('delete', $topic))->toBeFalse();
});

it('allows author to delete their own topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true], $category);
    $topic = createViewableTopic($forum, $user);

    expect(Gate::forUser($user)->check('delete', $topic))->toBeTrue();
});

it('denies author from deleting locked topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true], $category);
    $topic = createViewableTopic($forum, $user);
    $topic->update(['is_locked' => true]);

    expect(Gate::forUser($user)->check('delete', $topic))->toBeFalse();
});

it('allows user with forum delete permission to delete non-authored topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true, 'delete' => true], $category);
    $topic = createViewableTopic($forum);

    expect(Gate::forUser($user)->check('delete', $topic))->toBeTrue();
});

it('denies user without delete permission from deleting non-authored topic', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true, 'delete' => false], $category);
    $topic = createViewableTopic($forum);

    expect(Gate::forUser($user)->check('delete', $topic))->toBeFalse();
});

it('denies deleting locked topic even with forum delete permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPermissions($group, ['read' => true, 'delete' => true], $category);
    $topic = createViewableTopic($forum);
    $topic->update(['is_locked' => true]);

    expect(Gate::forUser($user)->check('delete', $topic))->toBeFalse();
});

// Edge cases

it('allows viewing topic when category is inactive but forum has no category', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => null,
    ]);
    $forum->groups()->attach($group, [
        'create' => false, 'read' => true, 'update' => false, 'delete' => false,
        'moderate' => false, 'reply' => false, 'report' => false,
        'pin' => false, 'lock' => false, 'move' => false,
    ]);
    $topic = createViewableTopic($forum);

    expect(Gate::forUser($user)->check('view', $topic))->toBeTrue();
});

it('denies viewing topic when category read permission is missing', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    // Category has no read permission
    $category->groups()->attach($group, [
        'create' => false, 'read' => false, 'update' => false, 'delete' => false,
        'moderate' => false, 'reply' => false, 'report' => false,
        'pin' => false, 'lock' => false, 'move' => false,
    ]);

    $forum = Forum::factory()->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);
    // Forum has read permission but category doesn't
    $forum->groups()->attach($group, [
        'create' => false, 'read' => true, 'update' => false, 'delete' => false,
        'moderate' => false, 'reply' => false, 'report' => false,
        'pin' => false, 'lock' => false, 'move' => false,
    ]);

    $topic = createViewableTopic($forum);

    expect(Gate::forUser($user)->check('view', $topic))->toBeFalse();
});
