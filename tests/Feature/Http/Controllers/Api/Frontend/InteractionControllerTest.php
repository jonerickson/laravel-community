<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Follow;
use App\Models\Forum;
use App\Models\Group;
use App\Models\Like;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    $this->appUrl = config('app.url');
});

/*
|--------------------------------------------------------------------------
| Like Controller Tests
|--------------------------------------------------------------------------
*/

test('like a post successfully', function (): void {
    $user = User::factory()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create(['topic_id' => $topic->id]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'post',
            'id' => $post->id,
            'emoji' => '👍',
        ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'likesSummary',
            'userReactions',
        ],
    ]);

    $this->assertDatabaseHas('likes', [
        'likeable_type' => Post::class,
        'likeable_id' => $post->id,
        'created_by' => $user->id,
    ]);
});

test('like a comment successfully', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->blog()->published()->create();
    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $post->id,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'comment',
            'id' => $comment->id,
            'emoji' => '❤️',
        ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'likesSummary',
            'userReactions',
        ],
    ]);

    $this->assertDatabaseHas('likes', [
        'likeable_type' => Comment::class,
        'likeable_id' => $comment->id,
        'created_by' => $user->id,
    ]);
});

test('toggle like removes existing like', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->blog()->published()->create();

    // Create existing like
    $like = Like::factory()->create([
        'likeable_type' => Post::class,
        'likeable_id' => $post->id,
        'emoji' => 'U+1F44D', // 👍 in unicode
        'created_by' => $user->id,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'post',
            'id' => $post->id,
            'emoji' => '👍',
        ]);

    $response->assertOk();

    $this->assertDatabaseMissing('likes', [
        'id' => $like->id,
    ]);
});

test('like requires authentication', function (): void {
    $post = Post::factory()->blog()->published()->create();

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'post',
            'id' => $post->id,
            'emoji' => '👍',
        ]);

    $response->assertUnauthorized();
});

test('like fails with missing type', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->blog()->published()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'id' => $post->id,
            'emoji' => '👍',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

test('like fails with invalid type', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'invalid',
            'id' => 1,
            'emoji' => '👍',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

test('like fails with missing id', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'post',
            'emoji' => '👍',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['id']);
});

test('like fails with missing emoji', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->blog()->published()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['emoji']);
});

test('like fails with non-existent post', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'post',
            'id' => 99999,
            'emoji' => '👍',
        ]);

    $response->assertNotFound();
});

test('like fails with non-existent comment', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'comment',
            'id' => 99999,
            'emoji' => '👍',
        ]);

    $response->assertNotFound();
});

test('like returns likes summary with count and users', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->blog()->published()->create();

    // Create existing like from other user
    Like::factory()->create([
        'likeable_type' => Post::class,
        'likeable_id' => $post->id,
        'emoji' => 'U+1F44D', // 👍
        'created_by' => $otherUser->id,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'post',
            'id' => $post->id,
            'emoji' => '👍',
        ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'likesSummary' => [
                '*' => ['emoji', 'count', 'users'],
            ],
            'userReactions',
        ],
    ]);
});

test('like with different emoji adds new like', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->blog()->published()->create();

    // Create existing like with thumbs up
    Like::factory()->create([
        'likeable_type' => Post::class,
        'likeable_id' => $post->id,
        'emoji' => 'U+1F44D', // 👍
        'created_by' => $user->id,
    ]);

    Passport::actingAs($user);

    // Add a different emoji
    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/like', [
            'type' => 'post',
            'id' => $post->id,
            'emoji' => '❤️',
        ]);

    $response->assertOk();

    // Both likes should exist
    $this->assertDatabaseCount('likes', 2);
});

/*
|--------------------------------------------------------------------------
| Follow Controller Tests
|--------------------------------------------------------------------------
*/

test('follow a forum successfully', function (): void {
    $user = User::factory()->create();
    $forum = Forum::factory()->create(['is_active' => true]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'type' => 'forum',
            'id' => $forum->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'You have successfully followed the forum.',
    ]);

    $this->assertDatabaseHas('follows', [
        'followable_type' => Forum::class,
        'followable_id' => $forum->id,
        'created_by' => $user->id,
    ]);
});

test('follow a topic successfully', function (): void {
    $user = User::factory()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'You have successfully followed the topic.',
    ]);

    $this->assertDatabaseHas('follows', [
        'followable_type' => Topic::class,
        'followable_id' => $topic->id,
        'created_by' => $user->id,
    ]);
});

test('unfollow a forum successfully', function (): void {
    $user = User::factory()->create();
    $forum = Forum::factory()->create(['is_active' => true]);

    // Create existing follow
    Follow::create([
        'followable_type' => Forum::class,
        'followable_id' => $forum->id,
        'created_by' => $user->id,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson('/api/follow', [
            'type' => 'forum',
            'id' => $forum->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'You have successfully unfollowed the forum.',
    ]);

    $this->assertDatabaseMissing('follows', [
        'followable_type' => Forum::class,
        'followable_id' => $forum->id,
        'created_by' => $user->id,
    ]);
});

test('unfollow a topic successfully', function (): void {
    $user = User::factory()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    // Create existing follow
    Follow::create([
        'followable_type' => Topic::class,
        'followable_id' => $topic->id,
        'created_by' => $user->id,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson('/api/follow', [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'You have successfully unfollowed the topic.',
    ]);

    $this->assertDatabaseMissing('follows', [
        'followable_type' => Topic::class,
        'followable_id' => $topic->id,
        'created_by' => $user->id,
    ]);
});

test('follow requires authentication', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'type' => 'forum',
            'id' => $forum->id,
        ]);

    $response->assertUnauthorized();
});

test('unfollow requires authentication', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson('/api/follow', [
            'type' => 'forum',
            'id' => $forum->id,
        ]);

    $response->assertUnauthorized();
});

test('follow fails with missing type', function (): void {
    $user = User::factory()->create();
    $forum = Forum::factory()->create(['is_active' => true]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'id' => $forum->id,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

test('follow fails with invalid type', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'type' => 'invalid',
            'id' => 1,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

test('follow fails with missing id', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'type' => 'forum',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['id']);
});

test('follow fails with non-existent forum', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'type' => 'forum',
            'id' => 99999,
        ]);

    $response->assertNotFound();
});

test('follow fails with non-existent topic', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'type' => 'topic',
            'id' => 99999,
        ]);

    $response->assertNotFound();
});

test('following same forum twice uses updateOrCreate', function (): void {
    $user = User::factory()->create();
    $forum = Forum::factory()->create(['is_active' => true]);

    Passport::actingAs($user);

    // First follow
    $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'type' => 'forum',
            'id' => $forum->id,
        ]);

    // Second follow (should not create duplicate)
    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/follow', [
            'type' => 'forum',
            'id' => $forum->id,
        ]);

    $response->assertOk();

    $followCount = Follow::where('followable_type', Forum::class)
        ->where('followable_id', $forum->id)
        ->where('created_by', $user->id)
        ->count();

    expect($followCount)->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Pin Controller Tests
|--------------------------------------------------------------------------
*/

test('pin a topic successfully with permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    // Attach group with pin permission
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => true,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'is_pinned' => false,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/pin', [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'The topic has been successfully pinned.',
    ]);

    $this->assertTrue($topic->fresh()->is_pinned);
});

test('pin a post successfully with permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => true,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
        'is_pinned' => false,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/pin', [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully pinned.',
    ]);

    $this->assertTrue($post->fresh()->is_pinned);
});

test('unpin a topic successfully with permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => true,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'is_pinned' => true,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson('/api/pin', [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'The topic has been successfully unpinned.',
    ]);

    $this->assertFalse($topic->fresh()->is_pinned);
});

test('unpin a post successfully with permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => false,
        'reply' => false,
        'report' => false,
        'pin' => true,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
        'is_pinned' => true,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson('/api/pin', [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully unpinned.',
    ]);

    $this->assertFalse($post->fresh()->is_pinned);
});

test('pin requires authentication', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/pin', [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertUnauthorized();
});

test('unpin requires authentication', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson('/api/pin', [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertUnauthorized();
});

test('pin fails without permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    // Attach group with read but NO pin permission
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

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'is_pinned' => false,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/pin', [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertForbidden();
});

test('unpin fails without permission', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    // Attach group with read but NO pin permission
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

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'is_pinned' => true,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson('/api/pin', [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertForbidden();
});

test('pin fails with missing type', function (): void {
    $user = User::factory()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/pin', [
            'id' => $topic->id,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

test('pin fails with invalid type', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/pin', [
            'type' => 'invalid',
            'id' => 1,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

test('pin fails with missing id', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/pin', [
            'type' => 'topic',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['id']);
});

test('pin fails with non-existent topic', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/pin', [
            'type' => 'topic',
            'id' => 99999,
        ]);

    $response->assertNotFound();
});

test('pin fails with non-existent post', function (): void {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson('/api/pin', [
            'type' => 'post',
            'id' => 99999,
        ]);

    $response->assertNotFound();
});
