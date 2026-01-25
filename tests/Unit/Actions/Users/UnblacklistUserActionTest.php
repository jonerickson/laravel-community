<?php

declare(strict_types=1);

use App\Actions\Users\UnblacklistUserAction;
use App\Models\Blacklist;
use App\Models\Fingerprint;
use App\Models\User;

describe('UnblacklistUserAction', function (): void {
    test('unblacklists user and removes blacklist record', function (): void {
        $user = User::factory()->create();
        $user->blacklistResource('Previous ban');

        expect($user->is_blacklisted)->toBeTrue();

        $action = new UnblacklistUserAction($user);
        $result = $action();

        expect($result)->toBeTrue();

        // Refresh to clear cached is_blacklisted attribute
        $user->refresh();
        expect($user->is_blacklisted)->toBeFalse();

        // Blacklist record should be deleted
        expect(Blacklist::where('resource_type', User::class)->where('resource_id', $user->id)->exists())->toBeFalse();
    });

    test('unblacklists all user fingerprints', function (): void {
        $user = User::factory()->create();
        $fingerprint1 = Fingerprint::factory()->create(['user_id' => $user->id]);
        $fingerprint2 = Fingerprint::factory()->create(['user_id' => $user->id]);

        // Blacklist user and fingerprints
        $user->blacklistResource('User ban');
        $fingerprint1->blacklistResource('Fingerprint ban');
        $fingerprint2->blacklistResource('Fingerprint ban');

        expect($user->is_blacklisted)->toBeTrue();
        expect($fingerprint1->is_blacklisted)->toBeTrue();
        expect($fingerprint2->is_blacklisted)->toBeTrue();

        $action = new UnblacklistUserAction($user);
        $result = $action();

        expect($result)->toBeTrue();

        // All should be unblacklisted
        $user->refresh();
        $fingerprint1->refresh();
        $fingerprint2->refresh();

        expect($user->is_blacklisted)->toBeFalse();
        expect($fingerprint1->is_blacklisted)->toBeFalse();
        expect($fingerprint2->is_blacklisted)->toBeFalse();
    });

    test('returns true even when user was not blacklisted', function (): void {
        $user = User::factory()->create();

        expect($user->is_blacklisted)->toBeFalse();

        $action = new UnblacklistUserAction($user);
        $result = $action();

        // Action always returns true
        expect($result)->toBeTrue();
        expect($user->is_blacklisted)->toBeFalse();
    });

    test('handles user with no fingerprints', function (): void {
        $user = User::factory()->create();
        $user->blacklistResource('Ban');

        expect($user->fingerprints()->count())->toBe(0);

        $action = new UnblacklistUserAction($user);
        $result = $action();

        expect($result)->toBeTrue();
        $user->refresh();
        expect($user->is_blacklisted)->toBeFalse();
    });

    test('can be executed via static execute method', function (): void {
        $user = User::factory()->create();
        $user->blacklistResource('Ban');

        $result = UnblacklistUserAction::execute($user);

        expect($result)->toBeTrue();
        $user->refresh();
        expect($user->is_blacklisted)->toBeFalse();
    });

    test('only unblacklists fingerprints belonging to the user', function (): void {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1Fingerprint = Fingerprint::factory()->create(['user_id' => $user1->id]);
        $user2Fingerprint = Fingerprint::factory()->create(['user_id' => $user2->id]);

        // Blacklist both users and their fingerprints
        $user1->blacklistResource('Ban user 1');
        $user2->blacklistResource('Ban user 2');
        $user1Fingerprint->blacklistResource('Ban fp 1');
        $user2Fingerprint->blacklistResource('Ban fp 2');

        // Unblacklist user1 only
        $action = new UnblacklistUserAction($user1);
        $action();

        // User1 and their fingerprint should be unblacklisted
        $user1->refresh();
        $user1Fingerprint->refresh();
        expect($user1->is_blacklisted)->toBeFalse();
        expect($user1Fingerprint->is_blacklisted)->toBeFalse();

        // User2 and their fingerprint should still be blacklisted
        $user2->refresh();
        $user2Fingerprint->refresh();
        expect($user2->is_blacklisted)->toBeTrue();
        expect($user2Fingerprint->is_blacklisted)->toBeTrue();
    });

    test('removes all blacklist records for user and fingerprints', function (): void {
        $user = User::factory()->create();
        $fingerprints = Fingerprint::factory()->count(3)->create(['user_id' => $user->id]);

        // Blacklist user and all fingerprints
        $user->blacklistResource('User ban');
        foreach ($fingerprints as $fingerprint) {
            $fingerprint->blacklistResource('Fingerprint ban');
        }

        // Should have 4 blacklist records total
        expect(Blacklist::count())->toBe(4);

        $action = new UnblacklistUserAction($user);
        $action();

        // All blacklist records should be deleted
        expect(Blacklist::where('resource_type', User::class)->where('resource_id', $user->id)->exists())->toBeFalse();
        foreach ($fingerprints as $fingerprint) {
            expect(Blacklist::where('resource_type', Fingerprint::class)->where('resource_id', $fingerprint->id)->exists())->toBeFalse();
        }
    });

    test('handles fingerprints that were not blacklisted', function (): void {
        $user = User::factory()->create();
        $blacklistedFingerprint = Fingerprint::factory()->create(['user_id' => $user->id]);
        $unblacklistedFingerprint = Fingerprint::factory()->create(['user_id' => $user->id]);

        // Only blacklist user and one fingerprint
        $user->blacklistResource('Ban');
        $blacklistedFingerprint->blacklistResource('Fingerprint ban');

        expect($blacklistedFingerprint->is_blacklisted)->toBeTrue();
        expect($unblacklistedFingerprint->is_blacklisted)->toBeFalse();

        // Action should complete without error
        $action = new UnblacklistUserAction($user);
        $result = $action();

        expect($result)->toBeTrue();

        // Both fingerprints should be unblacklisted (one was already)
        $blacklistedFingerprint->refresh();
        $unblacklistedFingerprint->refresh();
        expect($blacklistedFingerprint->is_blacklisted)->toBeFalse();
        expect($unblacklistedFingerprint->is_blacklisted)->toBeFalse();
    });

    test('deletes blacklist record via morphOne relationship', function (): void {
        $user = User::factory()->create();
        $user->blacklistResource('Test ban');

        $blacklistId = $user->blacklist->id;
        expect(Blacklist::find($blacklistId))->not->toBeNull();

        $action = new UnblacklistUserAction($user);
        $action();

        // Blacklist record should be completely deleted from database
        expect(Blacklist::find($blacklistId))->toBeNull();
    });
});
