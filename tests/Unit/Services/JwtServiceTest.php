<?php

declare(strict_types=1);

use App\Data\ProductData;
use App\Data\SubscriptionData;
use App\Enums\ProductApprovalStatus;
use App\Enums\ProductType;
use App\Enums\SubscriptionStatus;
use App\Facades\PaymentProcessor;
use App\Models\Fingerprint;
use App\Models\User;
use App\Models\UserIntegration;
use App\Services\JwtService;
use Carbon\Carbon;

function createTestProductData(string $name = 'Test Product'): ProductData
{
    return ProductData::from([
        'id' => 1,
        'reference_id' => 'ref-123',
        'name' => $name,
        'slug' => 'test-product',
        'type' => ProductType::Product,
        'order' => 1,
        'is_featured' => false,
        'is_subscription_only' => false,
        'is_marketplace_product' => false,
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => true,
        'is_visible' => true,
        'trial_days' => 0,
        'allow_promotion_codes' => true,
        'allow_discount_codes' => true,
        'average_rating' => 0.0,
        'reviews_count' => 0,
        'prices' => [],
        'categories' => [],
    ]);
}

function createJwtService(string $appKey = 'test-app-key'): JwtService
{
    return new JwtService(appKey: $appKey);
}

/**
 * @return array<string, mixed>
 */
function decodeJwt(string $token): array
{
    $parts = explode('.', $token);

    return [
        'header' => json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true),
        'payload' => json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true),
        'signature' => $parts[2],
    ];
}

describe('generate', function (): void {
    test('creates a valid jwt structure', function (): void {
        $service = createJwtService();

        $token = $service->generate(['user_id' => 123]);

        $parts = explode('.', $token);

        expect($parts)->toHaveCount(3);
    });

    test('includes correct header', function (): void {
        $service = createJwtService();

        $token = $service->generate(['user_id' => 123]);

        $decoded = decodeJwt($token);

        expect($decoded['header'])
            ->toHaveKey('typ', 'JWT')
            ->toHaveKey('alg', 'HS256');
    });

    test('includes custom claims in payload', function (): void {
        $service = createJwtService();

        $token = $service->generate([
            'user_id' => 123,
            'role' => 'admin',
        ]);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])
            ->toHaveKey('user_id', 123)
            ->toHaveKey('role', 'admin');
    });

    test('includes iat claim with current timestamp', function (): void {
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 12, 0, 0));

        $service = createJwtService();

        $token = $service->generate(['test' => 'value']);

        $decoded = decodeJwt($token);

        expect($decoded['payload']['iat'])->toBe(Carbon::now()->getTimestamp());

        Carbon::setTestNow();
    });

    test('includes exp claim with default 300 seconds expiration', function (): void {
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 12, 0, 0));

        $service = createJwtService();

        $token = $service->generate(['test' => 'value']);

        $decoded = decodeJwt($token);

        expect($decoded['payload']['exp'])->toBe(Carbon::now()->getTimestamp() + 300);

        Carbon::setTestNow();
    });

    test('uses custom expiration when provided', function (): void {
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 12, 0, 0));

        $service = createJwtService();

        $token = $service->generate(['test' => 'value'], expiresIn: 600);

        $decoded = decodeJwt($token);

        expect($decoded['payload']['exp'])->toBe(Carbon::now()->getTimestamp() + 600);

        Carbon::setTestNow();
    });

    test('uses custom secret when provided', function (): void {
        $service = createJwtService(appKey: 'default-key');

        $tokenWithDefault = $service->generate(['test' => 'value']);
        $tokenWithCustom = $service->generate(['test' => 'value'], secret: 'custom-secret');

        expect($tokenWithDefault)->not->toBe($tokenWithCustom);
    });

    test('uses app key as default secret', function (): void {
        $service = createJwtService(appKey: 'my-app-key');

        $tokenWithDefault = $service->generate(['test' => 'value']);
        $tokenWithExplicitKey = $service->generate(['test' => 'value'], secret: 'my-app-key');

        $defaultDecoded = decodeJwt($tokenWithDefault);
        $explicitDecoded = decodeJwt($tokenWithExplicitKey);

        expect($defaultDecoded['signature'])->toBe($explicitDecoded['signature']);
    });

    test('generates different tokens with different secrets', function (): void {
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 12, 0, 0));

        $service = createJwtService();

        $token1 = $service->generate(['test' => 'value'], secret: 'secret-one');
        $token2 = $service->generate(['test' => 'value'], secret: 'secret-two');

        $decoded1 = decodeJwt($token1);
        $decoded2 = decodeJwt($token2);

        expect($decoded1['payload'])->toBe($decoded2['payload']);
        expect($decoded1['signature'])->not->toBe($decoded2['signature']);

        Carbon::setTestNow();
    });
});

describe('generateForUser', function (): void {
    test('includes user id as sub claim', function (): void {
        $user = User::factory()->create();

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload']['sub'])->toBe((string) $user->id);
    });

    test('includes user email claim', function (): void {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload']['email'])->toBe('test@example.com');
    });

    test('includes user name claim', function (): void {
        $user = User::factory()->create(['name' => 'John Doe']);

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload']['name'])->toBe('John Doe');
    });

    test('includes additional claims when provided', function (): void {
        $user = User::factory()->create();

        $service = createJwtService();

        $token = $service->generateForUser($user, additionalClaims: [
            'custom_field' => 'custom_value',
            'another_field' => 42,
        ]);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])
            ->toHaveKey('custom_field', 'custom_value')
            ->toHaveKey('another_field', 42);
    });

    test('uses custom expiration when provided', function (): void {
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 12, 0, 0));

        $user = User::factory()->create();

        $service = createJwtService();

        $token = $service->generateForUser($user, expiresIn: 3600);

        $decoded = decodeJwt($token);

        expect($decoded['payload']['exp'])->toBe(Carbon::now()->getTimestamp() + 3600);

        Carbon::setTestNow();
    });

    test('uses custom secret when provided', function (): void {
        $user = User::factory()->create();

        $service = createJwtService(appKey: 'default-key');

        $tokenWithDefault = $service->generateForUser($user);
        $tokenWithCustom = $service->generateForUser($user, secret: 'custom-secret');

        expect($tokenWithDefault)->not->toBe($tokenWithCustom);
    });

    test('includes user integrations as claims', function (): void {
        $user = User::factory()->create();

        UserIntegration::query()->create([
            'user_id' => $user->id,
            'provider' => 'discord',
            'provider_id' => '123456789',
        ]);

        UserIntegration::query()->create([
            'user_id' => $user->id,
            'provider' => 'roblox',
            'provider_id' => '987654321',
        ]);

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])
            ->toHaveKey('discord', '123456789')
            ->toHaveKey('roblox', '987654321');
    });

    test('works with user having no integrations', function (): void {
        $user = User::factory()->create();

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])
            ->toHaveKey('sub')
            ->toHaveKey('email')
            ->toHaveKey('name')
            ->not->toHaveKey('discord')
            ->not->toHaveKey('roblox');
    });

    test('loads integrations if not already loaded', function (): void {
        $user = User::factory()->create();

        UserIntegration::query()->create([
            'user_id' => $user->id,
            'provider' => 'discord',
            'provider_id' => 'discord-123',
        ]);

        $freshUser = User::query()->find($user->id);

        expect($freshUser->relationLoaded('integrations'))->toBeFalse();

        $service = createJwtService();
        $token = $service->generateForUser($freshUser);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])->toHaveKey('discord', 'discord-123');
    });

    test('includes subscription name and status when user has subscription', function (): void {
        $user = User::factory()->create();

        $subscriptionData = SubscriptionData::from([
            'name' => 'default',
            'status' => SubscriptionStatus::Active,
            'does_not_expire' => false,
            'product' => createTestProductData('Premium Plan'),
        ]);

        PaymentProcessor::shouldReceive('currentSubscription')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn($subscriptionData);

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])
            ->toHaveKey('subscription_name', 'Premium Plan')
            ->toHaveKey('subscription_status', 'Active');
    });

    test('includes subscription with different statuses', function (): void {
        $user = User::factory()->create();

        $subscriptionData = SubscriptionData::from([
            'name' => 'default',
            'status' => SubscriptionStatus::Trialing,
            'does_not_expire' => false,
            'product' => createTestProductData('Enterprise'),
        ]);

        PaymentProcessor::shouldReceive('currentSubscription')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn($subscriptionData);

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])
            ->toHaveKey('subscription_name', 'Enterprise')
            ->toHaveKey('subscription_status', 'Trialing');
    });

    test('does not include subscription claims when user has no subscription', function (): void {
        $user = User::factory()->create();

        PaymentProcessor::shouldReceive('currentSubscription')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturnNull();

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])
            ->not->toHaveKey('subscription_name')
            ->not->toHaveKey('subscription_status');
    });

    test('handles subscription with null status', function (): void {
        $user = User::factory()->create();

        $subscriptionData = SubscriptionData::from([
            'name' => 'default',
            'status' => null,
            'does_not_expire' => true,
            'product' => createTestProductData('Basic'),
        ]);

        PaymentProcessor::shouldReceive('currentSubscription')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn($subscriptionData);

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])
            ->toHaveKey('subscription_name', 'Basic')
            ->toHaveKey('subscription_status', null);
    });

    test('handles subscription with null product', function (): void {
        $user = User::factory()->create();

        $subscriptionData = SubscriptionData::from([
            'name' => 'default',
            'status' => SubscriptionStatus::Active,
            'does_not_expire' => false,
            'product' => null,
        ]);

        PaymentProcessor::shouldReceive('currentSubscription')
            ->with(Mockery::on(fn ($arg): bool => $arg->id === $user->id))
            ->andReturn($subscriptionData);

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])
            ->toHaveKey('subscription_name', null)
            ->toHaveKey('subscription_status', 'Active');
    });

    test('includes fingerprint when user has a fingerprint', function (): void {
        $user = User::factory()->create();

        Fingerprint::factory()->create([
            'user_id' => $user->id,
            'fingerprint_id' => 'fp_abc123xyz',
        ]);

        PaymentProcessor::shouldReceive('currentSubscription')
            ->andReturnNull();

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])->toHaveKey('fingerprint', 'fp_abc123xyz');
    });

    test('does not include fingerprint when user has no fingerprints', function (): void {
        $user = User::factory()->create();

        PaymentProcessor::shouldReceive('currentSubscription')
            ->andReturnNull();

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])->not->toHaveKey('fingerprint');
    });

    test('uses latest fingerprint when user has multiple fingerprints', function (): void {
        $user = User::factory()->create();

        Fingerprint::factory()->create([
            'user_id' => $user->id,
            'fingerprint_id' => 'fp_older_fingerprint',
            'created_at' => now()->subDays(5),
        ]);

        Fingerprint::factory()->create([
            'user_id' => $user->id,
            'fingerprint_id' => 'fp_latest_fingerprint',
            'created_at' => now(),
        ]);

        Fingerprint::factory()->create([
            'user_id' => $user->id,
            'fingerprint_id' => 'fp_middle_fingerprint',
            'created_at' => now()->subDays(2),
        ]);

        PaymentProcessor::shouldReceive('currentSubscription')
            ->andReturnNull();

        $service = createJwtService();

        $token = $service->generateForUser($user);

        $decoded = decodeJwt($token);

        expect($decoded['payload'])->toHaveKey('fingerprint', 'fp_latest_fingerprint');
    });
});
