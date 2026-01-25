<?php

declare(strict_types=1);

use App\Models\Forum;
use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    $this->appUrl = config('app.url');
});

it('can lock a topic as admin', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $topic = Topic::factory()->create(['is_locked' => false]);

    $response = $this->postJson(route('api.lock.store'), [
        'type' => 'topic',
        'id' => $topic->id,
    ]);

    $response->assertSuccessful();

    expect($topic->fresh()->is_locked)->toBeTrue();
});

it('can unlock a topic as admin', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $topic = Topic::factory()->create(['is_locked' => true]);

    $response = $this->deleteJson(route('api.lock.destroy'), [
        'type' => 'topic',
        'id' => $topic->id,
    ]);

    $response->assertSuccessful();

    expect($topic->fresh()->is_locked)->toBeFalse();
});

it('can lock a topic with lock permission via group', function (): void {
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
        'lock' => true,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'is_locked' => false,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson(route('api.lock.store'), [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The topic has been successfully locked.',
    ]);

    expect($topic->fresh()->is_locked)->toBeTrue();
});

it('can unlock a topic with lock permission via group', function (): void {
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
        'lock' => true,
        'move' => false,
    ]);

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'is_locked' => true,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson(route('api.lock.destroy'), [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'The topic has been successfully unlocked.',
    ]);

    expect($topic->fresh()->is_locked)->toBeFalse();
});

it('cannot lock a topic without lock permission', function (): void {
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

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'is_locked' => false,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->postJson(route('api.lock.store'), [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertForbidden();
});

it('cannot unlock a topic without lock permission', function (): void {
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

    $topic = Topic::factory()->create([
        'forum_id' => $forum->id,
        'is_locked' => true,
    ]);

    Passport::actingAs($user);

    $response = $this->withHeader('referer', $this->appUrl)
        ->deleteJson(route('api.lock.destroy'), [
            'type' => 'topic',
            'id' => $topic->id,
        ]);

    $response->assertForbidden();
});

it('requires type and id for locking', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.lock.store'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type', 'id']);
});

it('requires type and id for unlocking', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.lock.destroy'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type', 'id']);
});

it('requires valid type for locking', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.lock.store'), [
        'type' => 'invalid',
        'id' => 1,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type']);
});

it('requires valid topic id for locking', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.lock.store'), [
        'type' => 'topic',
        'id' => 99999,
    ]);

    $response->assertNotFound();
});

it('requires valid topic id for unlocking', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.lock.destroy'), [
        'type' => 'topic',
        'id' => 99999,
    ]);

    $response->assertNotFound();
});

it('requires authentication for locking', function (): void {
    $topic = Topic::factory()->create();

    $response = $this->postJson(route('api.lock.store'), [
        'type' => 'topic',
        'id' => $topic->id,
    ]);

    $response->assertUnauthorized();
});

it('requires authentication for unlocking', function (): void {
    $topic = Topic::factory()->create();

    $response = $this->deleteJson(route('api.lock.destroy'), [
        'type' => 'topic',
        'id' => $topic->id,
    ]);

    $response->assertUnauthorized();
});
