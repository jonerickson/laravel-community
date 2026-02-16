<?php

declare(strict_types=1);

use App\Enums\PolicyConsentContext;
use App\Models\Policy;
use App\Models\PolicyConsent;
use App\Models\User;

beforeEach(function (): void {
    $this->policy = Policy::factory()->create([
        'is_active' => true,
        'requires_acceptance' => true,
        'version' => 'v1.0.0',
        'effective_at' => now()->subDay(),
    ]);
});

describe('priority order', function (): void {
    test('email middleware fires first when user has no email', function (): void {
        $user = User::factory()->unverified()->notOnboarded()->create([
            'email' => null,
            'password' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('set-email.notice'));
    });

    test('password middleware fires second when user has email but no password', function (): void {
        $user = User::factory()->unverified()->notOnboarded()->create([
            'password' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('set-password.notice'));
    });

    test('verified middleware fires third when user has email and password but is not verified', function (): void {
        $user = User::factory()->unverified()->notOnboarded()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('verification.notice'));
    });

    test('policies middleware fires fourth when user is verified but has outstanding policies', function (): void {
        $user = User::factory()->notOnboarded()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('policies.accept.notice'));
    });

    test('onboarding middleware fires last when all other conditions are met', function (): void {
        $user = User::factory()->notOnboarded()->create();

        PolicyConsent::factory()->create([
            'user_id' => $user->id,
            'policy_id' => $this->policy->id,
            'context' => PolicyConsentContext::Acceptance,
            'version' => 'v1.0.0',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('onboarding'));
    });

    test('user passes through all middleware when all conditions are met', function (): void {
        $user = User::factory()->create();

        PolicyConsent::factory()->create([
            'user_id' => $user->id,
            'policy_id' => $this->policy->id,
            'context' => PolicyConsentContext::Acceptance,
            'version' => 'v1.0.0',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    });
});

describe('forced action routes bypass all middleware', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->unverified()->notOnboarded()->create([
            'email' => null,
            'password' => null,
        ]);
    });

    test('set-email notice is accessible', function (): void {
        $response = $this->actingAs($this->user)->get(route('set-email.notice'));

        $response->assertOk();
    });

    test('set-password notice is accessible', function (): void {
        $response = $this->actingAs($this->user)->get(route('set-password.notice'));

        $response->assertOk();
    });

    test('verification notice is accessible', function (): void {
        $response = $this->actingAs($this->user)->get(route('verification.notice'));

        $response->assertOk();
    });

    test('accept-policies notice is accessible', function (): void {
        $response = $this->actingAs($this->user)->get(route('policies.accept.notice'));

        $response->assertOk();
    });

    test('onboarding is accessible', function (): void {
        $response = $this->actingAs($this->user)->get(route('onboarding'));

        $response->assertOk();
    });

    test('logout is accessible', function (): void {
        $response = $this->actingAs($this->user)->post(route('logout'));

        $response->assertRedirect('/');
    });
});
