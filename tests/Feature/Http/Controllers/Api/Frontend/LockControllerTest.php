<?php

declare(strict_types=1);

use App\Models\Topic;
use App\Models\User;
use Laravel\Passport\Passport;

it('can lock a topic', function (): void {
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

it('can unlock a topic', function (): void {
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

it('requires topic_id for locking', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.lock.store'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type', 'id']);
});

it('requires topic_id for unlocking', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->deleteJson(route('api.lock.destroy'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['type', 'id']);
});

it('requires valid topic_id', function (): void {
    $user = User::factory()->asAdmin()->create();
    Passport::actingAs($user, ['*']);

    $response = $this->postJson(route('api.lock.store'), [
        'type' => 'topic',
        'id' => 99999,
    ]);

    $response->assertNotFound();
});

it('requires authentication', function (): void {
    $topic = Topic::factory()->create();

    $response = $this->postJson(route('api.lock.store'), [
        'topic_id' => $topic->id,
    ]);

    $response->assertUnauthorized();
});
