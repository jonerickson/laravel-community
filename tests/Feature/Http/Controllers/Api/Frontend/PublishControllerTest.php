<?php

declare(strict_types=1);

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

it('can publish a post as admin', function (): void {
    $user = User::factory()->asAdmin()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->create([
        'topic_id' => $topic->id,
        'is_published' => false,
        'published_at' => null,
    ]);

    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.publish.store'), [
        'type' => 'post',
        'id' => $post->id,
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully published.',
    ]);

    $post->refresh();
    expect($post->is_published)->toBeTrue();
    expect($post->published_at)->not->toBeNull();
});

it('can unpublish a post as admin', function (): void {
    $user = User::factory()->asAdmin()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create([
        'topic_id' => $topic->id,
    ]);

    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.publish.destroy'), [
        'type' => 'post',
        'id' => $post->id,
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully unpublished.',
    ]);

    $post->refresh();
    expect($post->is_published)->toBeFalse();
    expect($post->published_at)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Group Permission Tests
|--------------------------------------------------------------------------
*/

it('can publish a post with moderate permission via group', function (): void {
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
    $post = Post::factory()->forum()->create([
        'topic_id' => $topic->id,
        'is_published' => false,
        'published_at' => null,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson(route('api.publish.store'), [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully published.',
    ]);

    $post->refresh();
    expect($post->is_published)->toBeTrue();
    expect($post->published_at)->not->toBeNull();
});

it('can unpublish a post with moderate permission via group', function (): void {
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
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson(route('api.publish.destroy'), [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The post has been successfully unpublished.',
    ]);

    $post->refresh();
    expect($post->is_published)->toBeFalse();
    expect($post->published_at)->toBeNull();
});

it('cannot publish a post without moderate permission', function (): void {
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
    $post = Post::factory()->forum()->create([
        'topic_id' => $topic->id,
        'is_published' => false,
        'published_at' => null,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson(route('api.publish.store'), [
            'type' => 'post',
            'id' => $post->id,
        ]);

    $response->assertForbidden();
});

it('cannot unpublish a post without moderate permission', function (): void {
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
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson(route('api.publish.destroy'), [
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

it('requires authentication for publishing', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->create(['topic_id' => $topic->id]);

    $response = $this->postJson(route('api.publish.store'), [
        'type' => 'post',
        'id' => $post->id,
    ]);

    $response->assertUnauthorized();
});

it('requires authentication for unpublishing', function (): void {
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);
    $post = Post::factory()->forum()->published()->create(['topic_id' => $topic->id]);

    $response = $this->deleteJson(route('api.publish.destroy'), [
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

it('requires type and id for publishing', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.publish.store'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type', 'id']);
});

it('requires type and id for unpublishing', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.publish.destroy'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type', 'id']);
});

it('requires valid type for publishing', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.publish.store'), [
        'type' => 'invalid',
        'id' => 1,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

it('requires valid type for unpublishing', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.publish.destroy'), [
        'type' => 'invalid',
        'id' => 1,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

it('returns 404 for non-existent post when publishing', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.publish.store'), [
        'type' => 'post',
        'id' => 99999,
    ]);

    $response->assertNotFound();
});

it('returns 404 for non-existent post when unpublishing', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.publish.destroy'), [
        'type' => 'post',
        'id' => 99999,
    ]);

    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Publishable Trait Behavior Tests
|--------------------------------------------------------------------------
*/

it('preserves existing published_at when publishing already published post', function (): void {
    $user = User::factory()->asAdmin()->create();
    $forum = Forum::factory()->create(['is_active' => true]);
    $topic = Topic::factory()->create(['forum_id' => $forum->id]);

    $existingPublishedAt = now()->subDays(5);
    $post = Post::factory()->forum()->create([
        'topic_id' => $topic->id,
        'is_published' => true,
        'published_at' => $existingPublishedAt,
    ]);

    // Simulate unpublishing first
    $post->unpublish();

    expect($post->is_published)->toBeFalse();
    expect($post->published_at)->toBeNull();

    // Now publish it again
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.publish.store'), [
        'type' => 'post',
        'id' => $post->id,
    ]);

    $response->assertSuccessful();

    $post->refresh();
    expect($post->is_published)->toBeTrue();
    // published_at should be set to current time, not the old one
    expect($post->published_at)->not->toBeNull();
});
