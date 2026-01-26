<?php

declare(strict_types=1);

use App\Enums\PostType;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\WarningConsequenceType;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\Post;
use App\Models\Report;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserWarning;
use App\Models\Warning;
use App\Models\WarningConsequence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    Group::factory()->asDefaultMemberGroup()->create();
});

function createForumWithPostPermissions(Group $group, array $overrides = [], ?ForumCategory $category = null): Forum
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

function createViewablePost(?Forum $forum = null, ?User $author = null): Post
{
    $author ??= User::factory()->create();
    $forum ??= Forum::factory()->create(['is_active' => true]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'created_by' => $author->id,
    ]);

    return Post::factory()->published()->create([
        'type' => PostType::Forum,
        'topic_id' => $topic->id,
        'created_by' => $author->id,
        'is_approved' => true,
        'is_published' => true,
        'published_at' => now()->subMinute(),
    ]);
}

function addActiveConsequence(User $user, WarningConsequenceType $type): void
{
    $admin = User::factory()->create();
    $warning = Warning::create([
        'name' => 'Test Warning',
        'points' => 1,
        'days_applied' => 7,
    ]);
    $consequence = WarningConsequence::create([
        'type' => $type,
        'threshold' => 1,
        'duration_days' => 7,
    ]);
    UserWarning::create([
        'user_id' => $user->id,
        'warning_id' => $warning->id,
        'warning_consequence_id' => $consequence->id,
        'created_by' => $admin->id,
        'reason' => 'Test consequence',
        'points_at_issue' => 1,
        'points_expire_at' => now()->addDays(7),
        'consequence_expires_at' => now()->addDays(7),
    ]);
}

// viewAny

it('allows anyone to view any posts', function (): void {
    expect(Gate::forUser(null)->check('viewAny', Post::class))->toBeTrue();

    $user = User::factory()->create();
    expect(Gate::forUser($user)->check('viewAny', Post::class))->toBeTrue();
});

// view

it('allows viewing approved published post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum);

    expect(Gate::forUser($user)->check('view', $post))->toBeTrue();
});

it('allows guest to view approved published post', function (): void {
    $guestGroup = Group::factory()->asDefaultGuest()->create(['is_active' => true]);
    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($guestGroup, ['read' => true], $category);
    $post = createViewablePost($forum);

    expect(Gate::forUser(null)->check('view', $post))->toBeTrue();
});

it('denies viewing unapproved post for non-author', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum);
    $post->update(['is_approved' => false]);

    expect(Gate::forUser($user)->check('view', $post))->toBeFalse();
});

it('allows author to view their own unapproved post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum, $user);
    $post->update(['is_approved' => false]);

    expect(Gate::forUser($user)->check('view', $post))->toBeTrue();
});

it('allows moderator to view unapproved post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true, 'moderate' => true], $category);
    $post = createViewablePost($forum);
    $post->update(['is_approved' => false]);

    expect(Gate::forUser($user)->check('view', $post))->toBeTrue();
});

it('denies viewing unpublished post for non-author', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum);
    $post->update(['is_published' => false]);

    expect(Gate::forUser($user)->check('view', $post))->toBeFalse();
});

it('allows author to view their own unpublished post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum, $user);
    $post->update(['is_published' => false]);

    expect(Gate::forUser($user)->check('view', $post))->toBeTrue();
});

it('denies viewing reported post for non-author', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum);

    $reporter = User::factory()->create();
    Auth::login($reporter);
    Report::create([
        'reportable_id' => $post->id,
        'reportable_type' => Post::class,
        'reason' => ReportReason::Spam,
        'status' => ReportStatus::Pending,
        'created_by' => $reporter->id,
    ]);
    Auth::logout();

    expect(Gate::forUser($user)->check('view', $post->refresh()))->toBeFalse();
});

it('allows author to view their own reported post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum, $user);

    $reporter = User::factory()->create();
    Auth::login($reporter);
    Report::create([
        'reportable_id' => $post->id,
        'reportable_type' => Post::class,
        'reason' => ReportReason::Spam,
        'status' => ReportStatus::Pending,
        'created_by' => $reporter->id,
    ]);
    Auth::logout();

    expect(Gate::forUser($user)->check('view', $post->refresh()))->toBeTrue();
});

it('allows moderator to view reported post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true, 'moderate' => true], $category);
    $post = createViewablePost($forum);

    $reporter = User::factory()->create();
    Auth::login($reporter);
    Report::create([
        'reportable_id' => $post->id,
        'reportable_type' => Post::class,
        'reason' => ReportReason::Spam,
        'status' => ReportStatus::Pending,
        'created_by' => $reporter->id,
    ]);
    Auth::logout();

    expect(Gate::forUser($user)->check('view', $post->refresh()))->toBeTrue();
});

it('denies viewing post with future published_at date', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum);
    $post->update(['published_at' => now()->addDay()]);

    expect(Gate::forUser($user)->check('view', $post))->toBeFalse();
});

it('allows viewing post with null published_at', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum);
    $post->update(['published_at' => null]);

    expect(Gate::forUser($user)->check('view', $post))->toBeTrue();
});

// create

it('denies guest from creating post', function (): void {
    expect(Gate::forUser(null)->check('create', Post::class))->toBeFalse();
});

it('allows authenticated user to create post', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->check('create', Post::class))->toBeTrue();
});

it('denies user with post restriction from creating post', function (): void {
    $user = User::factory()->create();
    addActiveConsequence($user, WarningConsequenceType::PostRestriction);
    $user->refresh();

    expect(Gate::forUser($user)->check('create', Post::class))->toBeFalse();
});

it('denies banned user from creating post', function (): void {
    $user = User::factory()->create();
    addActiveConsequence($user, WarningConsequenceType::Ban);
    $user->refresh();

    expect(Gate::forUser($user)->check('create', Post::class))->toBeFalse();
});

it('allows user with none consequence type to create post', function (): void {
    $user = User::factory()->create();
    addActiveConsequence($user, WarningConsequenceType::None);
    $user->refresh();

    expect(Gate::forUser($user)->check('create', Post::class))->toBeTrue();
});

it('allows user with moderate content consequence to create post', function (): void {
    $user = User::factory()->create();
    addActiveConsequence($user, WarningConsequenceType::ModerateContent);
    $user->refresh();

    expect(Gate::forUser($user)->check('create', Post::class))->toBeTrue();
});

// update

it('denies guest from updating post', function (): void {
    $post = createViewablePost();

    expect(Gate::forUser(null)->check('update', $post))->toBeFalse();
});

it('allows author to update their own post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum, $user);

    expect(Gate::forUser($user)->check('update', $post))->toBeTrue();
});

it('allows user with forum delete permission to update non-authored post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true, 'delete' => true], $category);
    $post = createViewablePost($forum);

    expect(Gate::forUser($user)->check('update', $post))->toBeTrue();
});

it('denies user without delete permission from updating non-authored post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true, 'delete' => false], $category);
    $post = createViewablePost($forum);

    expect(Gate::forUser($user)->check('update', $post))->toBeFalse();
});

// delete

it('denies guest from deleting post', function (): void {
    $post = createViewablePost();

    expect(Gate::forUser(null)->check('delete', $post))->toBeFalse();
});

it('allows author to delete their own post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true], $category);
    $post = createViewablePost($forum, $user);

    expect(Gate::forUser($user)->check('delete', $post))->toBeTrue();
});

it('allows user with forum delete permission to delete non-authored post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true, 'delete' => true], $category);
    $post = createViewablePost($forum);

    expect(Gate::forUser($user)->check('delete', $post))->toBeTrue();
});

it('denies user without delete permission from deleting non-authored post', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true, 'delete' => false], $category);
    $post = createViewablePost($forum);

    expect(Gate::forUser($user)->check('delete', $post))->toBeFalse();
});

// Edge cases

it('post view does not check forum read permission directly', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => false], $category);
    $post = createViewablePost($forum);

    // PostPolicy::view() checks post status flags, not forum permissions
    // Forum read permission is enforced at TopicPolicy level
    expect(Gate::forUser($user)->check('view', $post))->toBeTrue();
});

it('author can update post even without forum delete permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true, 'delete' => false], $category);
    $post = createViewablePost($forum, $user);

    expect(Gate::forUser($user)->check('update', $post))->toBeTrue();
});

it('author can delete post even without forum delete permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $category = ForumCategory::factory()->create(['is_active' => true]);
    $forum = createForumWithPostPermissions($group, ['read' => true, 'delete' => false], $category);
    $post = createViewablePost($forum, $user);

    expect(Gate::forUser($user)->check('delete', $post))->toBeTrue();
});
