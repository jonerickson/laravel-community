<?php

declare(strict_types=1);

use App\Enums\PolicyConsentContext;
use App\Models\Policy;
use App\Models\PolicyConsent;
use App\Models\User;

test('unauthenticated requests pass through', function (): void {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('user with no policies requiring acceptance passes through', function (): void {
    $user = User::factory()->create();

    Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => false,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});

test('user with outstanding policy is redirected to accept policies', function (): void {
    $user = User::factory()->create();

    Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('policies.accept.notice'));
});

test('user with current version consent passes through', function (): void {
    $user = User::factory()->create();

    $policy = Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    PolicyConsent::factory()->create([
        'user_id' => $user->id,
        'policy_id' => $policy->id,
        'context' => PolicyConsentContext::Acceptance,
        'version' => 'v1.0.0',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});

test('user with stale version consent is redirected', function (): void {
    $user = User::factory()->create();

    $policy = Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v2.0.0',
        'effective_at' => now()->subDay(),
    ]);

    PolicyConsent::factory()->create([
        'user_id' => $user->id,
        'policy_id' => $policy->id,
        'context' => PolicyConsentContext::Acceptance,
        'version' => 'v1.0.0',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('policies.accept.notice'));
});

test('accept policies notice route is not blocked', function (): void {
    $user = User::factory()->create();

    Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->get('/accept-policies');

    $response->assertOk();
});

test('logout route is not blocked', function (): void {
    $user = User::factory()->create();

    Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/');
});

test('json requests receive 403', function (): void {
    $user = User::factory()->create();

    Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->getJson('/dashboard');

    $response->assertForbidden();
});

test('inactive policies are not enforced', function (): void {
    $user = User::factory()->create();

    Policy::factory()->create([
        'is_active' => false,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});

test('future effective policies are not enforced', function (): void {
    $user = User::factory()->create();

    Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->addWeek(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});
