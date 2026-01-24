<?php

declare(strict_types=1);

use App\Models\Topic;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('can lock a topic', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $topic = Topic::factory()->create(['is_locked' => false]);

    $response = $this->postJson(route('api.lock.store'), [
        'topic_id' => $topic->id,
    ]);

    $response->assertSuccessful();

    expect($topic->fresh()->is_locked)->toBeTrue();
});

it('can unlock a topic', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $topic = Topic::factory()->create(['is_locked' => true]);

    $response = $this->deleteJson(route('api.lock.destroy'), [
        'topic_id' => $topic->id,
    ]);

    $response->assertSuccessful();

    expect($topic->fresh()->is_locked)->toBeFalse();
});

it('requires topic_id for locking', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson(route('api.lock.store'), []);

    $response->assertUnprocessable();
});

it('requires topic_id for unlocking', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->deleteJson(route('api.lock.destroy'), []);

    $response->assertUnprocessable();
});

it('requires valid topic_id', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson(route('api.lock.store'), [
        'topic_id' => 99999,
    ]);

    $response->assertUnprocessable();
});

it('requires authentication', function (): void {
    $topic = Topic::factory()->create();

    $response = $this->postJson(route('api.lock.store'), [
        'topic_id' => $topic->id,
    ]);

    $response->assertUnauthorized();
});
