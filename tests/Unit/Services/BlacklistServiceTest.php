<?php

declare(strict_types=1);

use App\Enums\FilterType;
use App\Enums\Role;
use App\Events\BlacklistMatch;
use App\Models\Blacklist;
use App\Models\Fingerprint;
use App\Models\User;
use App\Models\Whitelist;
use App\Services\BlacklistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

function createBlacklistService(?User $user = null, ?Fingerprint $fingerprint = null, ?string $ip = null): BlacklistService
{
    $request = Request::create('/', 'GET', [], [], [], $ip ? ['REMOTE_ADDR' => $ip] : []);

    return new BlacklistService(
        request: $request,
        user: $user,
        fingerprint: $fingerprint
    );
}

describe('isBlacklisted with roles', function (): void {
    test('returns false when user has any role', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::User);

        // Create a blacklist for this user
        Blacklist::factory()->create([
            'filter' => FilterType::User,
            'resource_type' => User::class,
            'resource_id' => $user->id,
        ]);

        $service = createBlacklistService(user: $user);
        $result = $service->isBlacklisted();

        expect($result)->toBeFalse();
    });

    test('returns false when user is admin', function (): void {
        $user = User::factory()->asAdmin()->create();

        // Create a blacklist for this user
        Blacklist::factory()->create([
            'filter' => FilterType::User,
            'resource_type' => User::class,
            'resource_id' => $user->id,
        ]);

        $service = createBlacklistService(user: $user);
        $result = $service->isBlacklisted();

        expect($result)->toBeFalse();
    });

    test('checks blacklist when user has no roles', function (): void {
        // Create a user without assigning any role
        $user = User::factory()->create();
        // Remove all roles
        $user->syncRoles([]);

        // Create a blacklist for this user
        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::User,
            'resource_type' => User::class,
            'resource_id' => $user->id,
        ]);

        $service = createBlacklistService(user: $user);
        $result = $service->isBlacklisted();

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });
});

describe('checkAllFilters', function (): void {
    test('returns false when nothing is blacklisted', function (): void {
        $user = User::factory()->create();
        $user->syncRoles([]);

        $service = createBlacklistService(user: $user, ip: '192.168.1.1');
        $result = $service->isBlacklisted();

        expect($result)->toBeFalse();
    });

    test('checks user blacklist first', function (): void {
        Event::fake([BlacklistMatch::class]);

        $user = User::factory()->create();
        $user->syncRoles([]);

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::User,
            'resource_type' => User::class,
            'resource_id' => $user->id,
        ]);

        $service = createBlacklistService(user: $user, ip: '192.168.1.1');
        $result = $service->isBlacklisted();

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);

        Event::assertDispatched(BlacklistMatch::class, fn (BlacklistMatch $event): bool => $event->blacklist->id === $blacklist->id
            && str_contains($event->content, (string) $user->id));
    });

    test('checks fingerprint blacklist when user not blacklisted', function (): void {
        Event::fake([BlacklistMatch::class]);

        $user = User::factory()->create();
        $user->syncRoles([]);

        $fingerprint = Fingerprint::factory()->create(['user_id' => $user->id]);
        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::Fingerprint,
            'resource_type' => Fingerprint::class,
            'resource_id' => $fingerprint->id,
        ]);

        $service = createBlacklistService(user: $user, fingerprint: $fingerprint, ip: '192.168.1.1');
        $result = $service->isBlacklisted();

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);

        Event::assertDispatched(BlacklistMatch::class, fn (BlacklistMatch $event): bool => $event->blacklist->id === $blacklist->id
            && str_contains($event->content, (string) $fingerprint->id));
    });

    test('checks IP blacklist when user and fingerprint not blacklisted', function (): void {
        Event::fake([BlacklistMatch::class]);

        $user = User::factory()->create();
        $user->syncRoles([]);

        $ip = '192.168.1.100';
        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::IpAddress,
            'content' => $ip,
        ]);

        $service = createBlacklistService(user: $user, ip: $ip);
        $result = $service->isBlacklisted();

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);

        Event::assertDispatched(BlacklistMatch::class, fn (BlacklistMatch $event): bool => $event->blacklist->id === $blacklist->id
            && str_contains($event->content, $ip));
    });

    test('returns false when guest with no blacklist', function (): void {
        $service = createBlacklistService(ip: '10.0.0.1');
        $result = $service->isBlacklisted();

        expect($result)->toBeFalse();
    });
});

describe('checkFilter with specific filter type', function (): void {
    test('checks user filter with User instance', function (): void {
        Event::fake([BlacklistMatch::class]);

        $user = User::factory()->create();
        $user->syncRoles([]);

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::User,
            'resource_type' => User::class,
            'resource_id' => $user->id,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted($user, FilterType::User);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });

    test('checks user filter with user ID', function (): void {
        Event::fake([BlacklistMatch::class]);

        $user = User::factory()->create();
        $user->syncRoles([]);

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::User,
            'resource_type' => User::class,
            'resource_id' => $user->id,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted($user->id, FilterType::User);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });

    test('checks fingerprint filter with Fingerprint instance', function (): void {
        Event::fake([BlacklistMatch::class]);

        $fingerprint = Fingerprint::factory()->guest()->create();

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::Fingerprint,
            'resource_type' => Fingerprint::class,
            'resource_id' => $fingerprint->id,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted($fingerprint, FilterType::Fingerprint);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });

    test('checks fingerprint filter with fingerprint ID', function (): void {
        Event::fake([BlacklistMatch::class]);

        $fingerprint = Fingerprint::factory()->guest()->create();

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::Fingerprint,
            'resource_type' => Fingerprint::class,
            'resource_id' => $fingerprint->id,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted($fingerprint->id, FilterType::Fingerprint);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });

    test('checks IP address filter', function (): void {
        Event::fake([BlacklistMatch::class]);

        $ip = '10.20.30.40';
        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::IpAddress,
            'content' => $ip,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted($ip, FilterType::IpAddress);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });

    test('returns false for non-blacklisted IP', function (): void {
        Blacklist::factory()->create([
            'filter' => FilterType::IpAddress,
            'content' => '10.20.30.40',
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted('192.168.1.1', FilterType::IpAddress);

        expect($result)->toBeFalse();
    });

    test('returns false for non-existent user ID', function (): void {
        $service = createBlacklistService();
        $result = $service->isBlacklisted(999999, FilterType::User);

        expect($result)->toBeFalse();
    });

    test('returns false for non-existent fingerprint ID', function (): void {
        $service = createBlacklistService();
        $result = $service->isBlacklisted(999999, FilterType::Fingerprint);

        expect($result)->toBeFalse();
    });
});

describe('string blacklist matching', function (): void {
    test('matches exact string content', function (): void {
        Event::fake([BlacklistMatch::class]);

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::String,
            'content' => 'badword',
            'is_regex' => false,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted('This contains badword in it', FilterType::String);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });

    test('matches case insensitively', function (): void {
        Event::fake([BlacklistMatch::class]);

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::String,
            'content' => 'BADWORD',
            'is_regex' => false,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted('this contains badword in it', FilterType::String);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });

    test('matches comma separated content', function (): void {
        Event::fake([BlacklistMatch::class]);

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::String,
            'content' => 'word1, word2, word3',
            'is_regex' => false,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted('this has word2 in it', FilterType::String);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });

    test('matches regex pattern', function (): void {
        Event::fake([BlacklistMatch::class]);

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::String,
            'content' => '/bad\s*word/',
            'is_regex' => true,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted('this has bad word in it', FilterType::String);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($blacklist->id);
    });

    test('handles invalid regex gracefully', function (): void {
        Blacklist::factory()->create([
            'filter' => FilterType::String,
            'content' => '/[invalid regex',
            'is_regex' => true,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted('some text', FilterType::String);

        expect($result)->toBeFalse();
    });

    test('returns false when string does not match any blacklist', function (): void {
        Blacklist::factory()->create([
            'filter' => FilterType::String,
            'content' => 'badword',
            'is_regex' => false,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted('this is perfectly fine', FilterType::String);

        expect($result)->toBeFalse();
    });

    test('checks multiple string blacklists', function (): void {
        Event::fake([BlacklistMatch::class]);

        Blacklist::factory()->create([
            'filter' => FilterType::String,
            'content' => 'firstword',
            'is_regex' => false,
        ]);

        $secondBlacklist = Blacklist::factory()->create([
            'filter' => FilterType::String,
            'content' => 'secondword',
            'is_regex' => false,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted('this has secondword in it', FilterType::String);

        expect($result)
            ->toBeInstanceOf(Blacklist::class)
            ->id->toBe($secondBlacklist->id);
    });
});

describe('whitelist bypasses blacklist', function (): void {
    test('whitelisted user bypasses user blacklist', function (): void {
        $user = User::factory()->create();
        $user->syncRoles([]);

        // Create blacklist for user
        Blacklist::factory()->create([
            'filter' => FilterType::User,
            'resource_type' => User::class,
            'resource_id' => $user->id,
        ]);

        // Create whitelist for user
        Whitelist::create([
            'filter' => FilterType::User,
            'resource_type' => User::class,
            'resource_id' => $user->id,
        ]);

        $service = createBlacklistService(user: $user);
        $result = $service->isBlacklisted();

        expect($result)->toBeFalse();
    });

    test('whitelisted fingerprint bypasses fingerprint blacklist', function (): void {
        $fingerprint = Fingerprint::factory()->guest()->create();

        // Create blacklist for fingerprint
        Blacklist::factory()->create([
            'filter' => FilterType::Fingerprint,
            'resource_type' => Fingerprint::class,
            'resource_id' => $fingerprint->id,
        ]);

        // Create whitelist for fingerprint
        Whitelist::create([
            'filter' => FilterType::Fingerprint,
            'resource_type' => Fingerprint::class,
            'resource_id' => $fingerprint->id,
        ]);

        $service = createBlacklistService(fingerprint: $fingerprint);
        $result = $service->isBlacklisted();

        expect($result)->toBeFalse();
    });

    test('whitelisted IP bypasses IP blacklist', function (): void {
        $ip = '192.168.1.50';

        // Create blacklist for IP
        Blacklist::factory()->create([
            'filter' => FilterType::IpAddress,
            'content' => $ip,
        ]);

        // Create whitelist for IP
        Whitelist::create([
            'filter' => FilterType::IpAddress,
            'content' => $ip,
        ]);

        $service = createBlacklistService(ip: $ip);
        $result = $service->isBlacklisted();

        expect($result)->toBeFalse();
    });

    test('whitelisted string bypasses string blacklist', function (): void {
        $content = 'allowed_word';

        // Create blacklist for string
        Blacklist::factory()->create([
            'filter' => FilterType::String,
            'content' => $content,
            'is_regex' => false,
        ]);

        // Create whitelist for string
        Whitelist::create([
            'filter' => FilterType::String,
            'content' => $content,
        ]);

        $service = createBlacklistService();
        $result = $service->isBlacklisted($content, FilterType::String);

        expect($result)->toBeFalse();
    });
});

describe('BlacklistMatch event', function (): void {
    test('fires BlacklistMatch event with correct data for user blacklist', function (): void {
        Event::fake([BlacklistMatch::class]);

        $user = User::factory()->create();
        $user->syncRoles([]);

        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::User,
            'resource_type' => User::class,
            'resource_id' => $user->id,
        ]);

        $service = createBlacklistService(user: $user);
        $service->isBlacklisted();

        Event::assertDispatched(BlacklistMatch::class, fn (BlacklistMatch $event): bool => $event->blacklist->id === $blacklist->id
            && $event->user?->id === $user->id
            && $event->content === 'User ID: '.$user->id);
    });

    test('fires BlacklistMatch event with correct data for IP blacklist', function (): void {
        Event::fake([BlacklistMatch::class]);

        $ip = '10.0.0.100';
        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::IpAddress,
            'content' => $ip,
        ]);

        $service = createBlacklistService(ip: $ip);
        $service->isBlacklisted();

        Event::assertDispatched(BlacklistMatch::class, fn (BlacklistMatch $event): bool => $event->blacklist->id === $blacklist->id
            && $event->content === 'IP Address: '.$ip);
    });

    test('fires BlacklistMatch event with null user for guest', function (): void {
        Event::fake([BlacklistMatch::class]);

        $ip = '10.0.0.200';
        $blacklist = Blacklist::factory()->create([
            'filter' => FilterType::IpAddress,
            'content' => $ip,
        ]);

        $service = createBlacklistService(ip: $ip);
        $service->isBlacklisted();

        Event::assertDispatched(BlacklistMatch::class, fn (BlacklistMatch $event): bool => is_null($event->user));
    });
});
