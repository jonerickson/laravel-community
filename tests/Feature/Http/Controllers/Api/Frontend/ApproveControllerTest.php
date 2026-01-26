<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Forum;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    $this->appUrl = config('app.url');
});

/*
|--------------------------------------------------------------------------
| Admin Tests
|--------------------------------------------------------------------------
*/

it('can approve a post as admin', function (): void {
    $user = User::factory()->asAdmin()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
        'is_approved' => false,
    ]);

    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.approve.store'), [
        'type' => 'post',
        'id' => $post->id,
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully approved.',
    ]);

    expect($post->fresh()->is_approved)->toBeTrue();
});

it('can unapprove a post as admin', function (): void {
    $user = User::factory()->asAdmin()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
        'is_approved' => true,
    ]);

    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.approve.destroy'), [
        'type' => 'post',
        'id' => $post->id,
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully unapproved.',
    ]);

    expect($post->fresh()->is_approved)->toBeFalse();
});

it('can approve a comment as admin', function (): void {
    $user = User::factory()->asAdmin()->create();
    $blogPost = Post::factory()->blog()->published()->create();
    $comment = Comment::factory()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $blogPost->id,
        'is_approved' => false,
    ]);

    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.approve.store'), [
        'type' => 'comment',
        'id' => $comment->id,
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The comment has been successfully approved.',
    ]);

    expect($comment->fresh()->is_approved)->toBeTrue();
});

it('can unapprove a comment as admin', function (): void {
    $user = User::factory()->asAdmin()->create();
    $blogPost = Post::factory()->blog()->published()->create();
    $comment = Comment::factory()->approved()->create([
        'commentable_type' => Post::class,
        'commentable_id' => $blogPost->id,
    ]);

    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.approve.destroy'), [
        'type' => 'comment',
        'id' => $comment->id,
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The comment has been successfully unapproved.',
    ]);

    expect($comment->fresh()->is_approved)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Group Permission Tests
|--------------------------------------------------------------------------
*/

it('can approve a post with moderate permission via group', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => true,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
        'is_approved' => false,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson(route('api.approve.store'), [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully approved.',
    ]);

    expect($post->fresh()->is_approved)->toBeTrue();
});

it('can unapprove a post with moderate permission via group', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create(['is_active' => true]);
    $user->groups()->attach($group);

    $forum = Forum::factory()->create(['is_active' => true]);
    $forum->groups()->attach($group, [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
        'moderate' => true,
        'reply' => false,
        'report' => false,
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
        'is_approved' => true,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson(route('api.approve.destroy'), [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully unapproved.',
    ]);

    expect($post->fresh()->is_approved)->toBeFalse();
});

it('cannot approve a post without moderate permission', function (): void {
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
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
        'is_approved' => false,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson(route('api.approve.store'), [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertForbidden();
});

it('cannot unapprove a post without moderate permission', function (): void {
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
        'pin' => false,
        'lock' => false,
        'move' => false,
    ]);

    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
        'is_approved' => true,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson(route('api.approve.destroy'), [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Authentication Tests
|--------------------------------------------------------------------------
*/

it('requires authentication for approving', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create(['topic_id' => $topic->id]);

    $response = $this->postJson(route('api.approve.store'), [
        'type' => 'post',
        'id' => $post->id,
    ]);

    $response->assertUnauthorized();
});

it('requires authentication for unapproving', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create(['topic_id' => $topic->id]);

    $response = $this->deleteJson(route('api.approve.destroy'), [
        'type' => 'post',
        'id' => $post->id,
    ]);

    $response->assertUnauthorized();
});

/*
|--------------------------------------------------------------------------
| Validation Tests
|--------------------------------------------------------------------------
*/

it('requires type and id for approving', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.approve.store'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type', 'id']);
});

it('requires type and id for unapproving', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.approve.destroy'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type', 'id']);
});

it('requires valid type for approving', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.approve.store'), [
        'type' => 'invalid',
        'id' => 1,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

it('requires valid type for unapproving', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.approve.destroy'), [
        'type' => 'invalid',
        'id' => 1,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

it('returns 404 for non-existent post when approving', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.approve.store'), [
        'type' => 'post',
        'id' => 99999,
    ]);

    $response->assertNotFound();
});

it('returns 404 for non-existent comment when approving', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.approve.store'), [
        'type' => 'comment',
        'id' => 99999,
    ]);

    $response->assertNotFound();
});

it('returns 404 for non-existent post when unapproving', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.approve.destroy'), [
        'type' => 'post',
        'id' => 99999,
    ]);

    $response->assertNotFound();
});

it('returns 404 for non-existent comment when unapproving', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.approve.destroy'), [
        'type' => 'comment',
        'id' => 99999,
    ]);

    $response->assertNotFound();
});
