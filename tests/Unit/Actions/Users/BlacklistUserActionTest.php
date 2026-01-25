<?php

declare(strict_types=1);

use App\Actions\Users\BlacklistUserAction;
use App\Enums\FilterType;
use App\Models\Blacklist;
use App\Models\Fingerprint;
use App\Models\User;

describe('BlacklistUserAction', function (): void {
    test('blacklists user and creates blacklist record', function (): void {
        $user = User::factory()->create();
        $reason = 'Violation of terms';

        $action = new BlacklistUserAction($user, $reason);
        $result = $action();

        expect($result)->toBeTrue();

        // Refresh to clear cached is_blacklisted attribute
        $user->refresh();
        expect($user->is_blacklisted)->toBeTrue();

        $blacklist = Blacklist::where('resource_type', User::class)
            ->where('resource_id', $user->id)
            ->first();

        expect($blacklist)->not->toBeNull();
        expect($blacklist->description)->toBe($reason);
        expect($blacklist->filter)->toBe(FilterType::User);
    });

    test('returns false when user is already blacklisted', function (): void {
        $user = User::factory()->create();
        $user->blacklistResource('Initial reason');

        expect($user->is_blacklisted)->toBeTrue();

        $action = new BlacklistUserAction($user, 'Another reason');
        $result = $action();

        expect($result)->toBeFalse();
        // Should still only have one blacklist record
        expect(Blacklist::where('resource_type', User::class)->where('resource_id', $user->id)->count())->toBe(1);
    });

    test('blacklists all user fingerprints', function (): void {
        $user = User::factory()->create();
        $fingerprint1 = Fingerprint::factory()->create(['user_id' => $user->id]);
        $fingerprint2 = Fingerprint::factory()->create(['user_id' => $user->id]);
        $reason = 'Account ban';

        $action = new BlacklistUserAction($user, $reason);
        $result = $action();

        expect($result)->toBeTrue();

        // Refresh to clear cached is_blacklisted attribute
        $user->refresh();

        // User should be blacklisted
        expect($user->is_blacklisted)->toBeTrue();

        // Both fingerprints should be blacklisted
        $fingerprint1->refresh();
        $fingerprint2->refresh();
        expect($fingerprint1->is_blacklisted)->toBeTrue();
        expect($fingerprint2->is_blacklisted)->toBeTrue();

        // Verify blacklist records exist with correct filter type
        $fp1Blacklist = Blacklist::where('resource_type', Fingerprint::class)
            ->where('resource_id', $fingerprint1->id)
            ->first();
        $fp2Blacklist = Blacklist::where('resource_type', Fingerprint::class)
            ->where('resource_id', $fingerprint2->id)
            ->first();

        expect($fp1Blacklist->filter)->toBe(FilterType::Fingerprint);
        expect($fp2Blacklist->filter)->toBe(FilterType::Fingerprint);
        expect($fp1Blacklist->description)->toBe($reason);
        expect($fp2Blacklist->description)->toBe($reason);
    });

    test('handles user with no fingerprints', function (): void {
        $user = User::factory()->create();
        $reason = 'Spam account';

        // Ensure no fingerprints exist
        expect($user->fingerprints()->count())->toBe(0);

        $action = new BlacklistUserAction($user, $reason);
        $result = $action();

        expect($result)->toBeTrue();

        // Refresh to clear cached is_blacklisted attribute
        $user->refresh();
        expect($user->is_blacklisted)->toBeTrue();
    });

    test('can be executed via static execute method', function (): void {
        $user = User::factory()->create();
        $reason = 'Policy violation';

        $result = BlacklistUserAction::execute($user, $reason);

        expect($result)->toBeTrue();

        // Refresh to clear cached is_blacklisted attribute
        $user->refresh();
        expect($user->is_blacklisted)->toBeTrue();
    });

    test('does not blacklist fingerprints when user already blacklisted', function (): void {
        $user = User::factory()->create();
        $fingerprint = Fingerprint::factory()->create(['user_id' => $user->id]);

        // Blacklist user first
        $user->blacklistResource('Initial ban');

        // Attempt to blacklist again
        $action = new BlacklistUserAction($user, 'Second attempt');
        $result = $action();

        expect($result)->toBeFalse();
        // Fingerprint should NOT be blacklisted since action returned early
        $fingerprint->refresh();
        expect($fingerprint->is_blacklisted)->toBeFalse();
    });

    test('stores reason in blacklist description', function (): void {
        $user = User::factory()->create();
        $reason = 'Fraudulent activity detected on January 25, 2026';

        $action = new BlacklistUserAction($user, $reason);
        $action();

        $blacklist = $user->blacklist;
        expect($blacklist->description)->toBe($reason);
    });

    test('creates separate blacklist records for user and each fingerprint', function (): void {
        $user = User::factory()->create();
        $fingerprints = Fingerprint::factory()->count(3)->create(['user_id' => $user->id]);
        $reason = 'Multiple violations';

        $action = new BlacklistUserAction($user, $reason);
        $action();

        // Should have 4 blacklist records total (1 user + 3 fingerprints)
        $userBlacklists = Blacklist::where('resource_type', User::class)->count();
        $fingerprintBlacklists = Blacklist::where('resource_type', Fingerprint::class)->count();

        expect($userBlacklists)->toBe(1);
        expect($fingerprintBlacklists)->toBe(3);
    });

    test('only blacklists fingerprints belonging to the user', function (): void {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1Fingerprint = Fingerprint::factory()->create(['user_id' => $user1->id]);
        $user2Fingerprint = Fingerprint::factory()->create(['user_id' => $user2->id]);

        $action = new BlacklistUserAction($user1, 'Ban user 1');
        $action();

        // User1's fingerprint should be blacklisted
        $user1Fingerprint->refresh();
        expect($user1Fingerprint->is_blacklisted)->toBeTrue();

        // User2's fingerprint should NOT be blacklisted
        $user2Fingerprint->refresh();
        expect($user2Fingerprint->is_blacklisted)->toBeFalse();
    });
});
