<?php

declare(strict_types=1);

use App\Actions\Policies\RecordPolicyConsentAction;
use App\Enums\PolicyConsentContext;
use App\Models\Policy;
use App\Models\PolicyConsent;
use App\Models\User;

describe('RecordPolicyConsentAction', function (): void {
    test('creates consent record with all metadata', function (): void {
        $user = User::factory()->create();
        $policy = Policy::factory()->create();

        RecordPolicyConsentAction::execute(
            $user,
            [$policy->id],
            PolicyConsentContext::Checkout,
            '192.168.1.1',
            'Mozilla/5.0',
            'fp-abc-123',
        );

        $consent = PolicyConsent::where('user_id', $user->id)
            ->where('policy_id', $policy->id)
            ->first();

        expect($consent)->not->toBeNull()
            ->and($consent->ip_address)->toBe('192.168.1.1')
            ->and($consent->user_agent)->toBe('Mozilla/5.0')
            ->and($consent->fingerprint_id)->toBe('fp-abc-123')
            ->and($consent->context)->toBe(PolicyConsentContext::Checkout)
            ->and($consent->consented_at)->not->toBeNull();
    });

    test('consents to multiple policies at once', function (): void {
        $user = User::factory()->create();
        $policies = Policy::factory()->count(3)->create();

        RecordPolicyConsentAction::execute(
            $user,
            $policies->pluck('id')->all(),
            PolicyConsentContext::Onboarding,
        );

        expect(PolicyConsent::where('user_id', $user->id)->count())->toBe(3);

        foreach ($policies as $policy) {
            expect(PolicyConsent::where('user_id', $user->id)->where('policy_id', $policy->id)->exists())->toBeTrue();
        }
    });

    test('accepts a collection of policies', function (): void {
        $user = User::factory()->create();
        $policies = Policy::factory()->count(2)->create();

        RecordPolicyConsentAction::execute(
            $user,
            $policies,
            PolicyConsentContext::Subscription,
        );

        expect(PolicyConsent::where('user_id', $user->id)->count())->toBe(2);
    });

    test('updates existing record on duplicate consent for same user, policy, and context', function (): void {
        $user = User::factory()->create();
        $policy = Policy::factory()->create();

        RecordPolicyConsentAction::execute(
            $user,
            [$policy->id],
            PolicyConsentContext::Checkout,
            '10.0.0.1',
            'OldAgent',
            'old-fp',
        );

        $originalConsent = PolicyConsent::where('user_id', $user->id)
            ->where('policy_id', $policy->id)
            ->first();
        $originalConsentedAt = $originalConsent->consented_at;

        $this->travel(5)->minutes();

        RecordPolicyConsentAction::execute(
            $user,
            [$policy->id],
            PolicyConsentContext::Checkout,
            '10.0.0.2',
            'NewAgent',
            'new-fp',
        );

        expect(PolicyConsent::where('user_id', $user->id)->where('policy_id', $policy->id)->count())->toBe(1);

        $updatedConsent = PolicyConsent::where('user_id', $user->id)
            ->where('policy_id', $policy->id)
            ->first();

        expect($updatedConsent->ip_address)->toBe('10.0.0.2')
            ->and($updatedConsent->user_agent)->toBe('NewAgent')
            ->and($updatedConsent->fingerprint_id)->toBe('new-fp')
            ->and($updatedConsent->consented_at->gt($originalConsentedAt))->toBeTrue();
    });

    test('allows separate consent records for different contexts', function (): void {
        $user = User::factory()->create();
        $policy = Policy::factory()->create();

        RecordPolicyConsentAction::execute($user, [$policy->id], PolicyConsentContext::Checkout);
        RecordPolicyConsentAction::execute($user, [$policy->id], PolicyConsentContext::Onboarding);

        expect(PolicyConsent::where('user_id', $user->id)->where('policy_id', $policy->id)->count())->toBe(2);
    });

    test('context enum is properly stored and cast', function (): void {
        $user = User::factory()->create();
        $policy = Policy::factory()->create();

        foreach (PolicyConsentContext::cases() as $context) {
            RecordPolicyConsentAction::execute($user, [$policy->id], $context);

            $consent = PolicyConsent::where('user_id', $user->id)
                ->where('policy_id', $policy->id)
                ->where('context', $context)
                ->first();

            expect($consent->context)->toBe($context)
                ->and($consent->context)->toBeInstanceOf(PolicyConsentContext::class);
        }
    });

    test('can be executed via static execute method', function (): void {
        $user = User::factory()->create();
        $policy = Policy::factory()->create();

        $result = RecordPolicyConsentAction::execute(
            $user,
            [$policy->id],
            PolicyConsentContext::Checkout,
        );

        expect($result)->toBeNull();
        expect(PolicyConsent::where('user_id', $user->id)->exists())->toBeTrue();
    });
});
