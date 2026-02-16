<?php

declare(strict_types=1);

use App\Enums\PolicyConsentContext;
use App\Models\Policy;
use App\Models\PolicyConsent;
use App\Models\User;

test('displays outstanding policies', function (): void {
    $user = User::factory()->create();

    Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->get('/accept-policies');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('auth/accept-policies')
        ->has('policies', 1)
    );
});

test('redirects to dashboard when no outstanding policies', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/accept-policies');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('records consent with correct version and acceptance context', function (): void {
    $user = User::factory()->create();

    $policy = Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    $this->actingAs($user)->post('/accept-policies');

    $consent = PolicyConsent::where('user_id', $user->id)
        ->where('policy_id', $policy->id)
        ->where('context', PolicyConsentContext::Acceptance)
        ->first();

    expect($consent)->not->toBeNull()
        ->and($consent->version)->toBe('v1.0.0')
        ->and($consent->context)->toBe(PolicyConsentContext::Acceptance);
});

test('redirects to intended url after acceptance', function (): void {
    $user = User::factory()->create();

    Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)->post('/accept-policies');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('accepts multiple outstanding policies at once', function (): void {
    $user = User::factory()->create();

    $policies = Policy::factory()->count(3)->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);

    $this->actingAs($user)->post('/accept-policies');

    foreach ($policies as $policy) {
        expect(PolicyConsent::where('user_id', $user->id)
            ->where('policy_id', $policy->id)
            ->where('context', PolicyConsentContext::Acceptance)
            ->exists()
        )->toBeTrue();
    }
});

test('guests cannot access accept policies page', function (): void {
    $response = $this->get('/accept-policies');

    $response->assertRedirect('/login');
});
