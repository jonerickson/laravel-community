<?php

declare(strict_types=1);

use App\Drivers\Payouts\NullDriver;
use App\Drivers\Payouts\PayoutProcessor;
use App\Models\Payout;
use App\Models\User;

describe('NullDriver for Payouts', function (): void {
    beforeEach(function (): void {
        $this->driver = new NullDriver;
    });

    test('implements PayoutProcessor interface', function (): void {
        expect($this->driver)->toBeInstanceOf(PayoutProcessor::class);
    });

    describe('Connected Account methods', function (): void {
        test('createConnectedAccount returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->createConnectedAccount($user);

            expect($result)->toBeNull();
        });

        test('createConnectedAccount returns null with options', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->createConnectedAccount($user, [
                'type' => 'express',
                'country' => 'US',
                'capabilities' => ['card_payments' => ['requested' => true]],
            ]);

            expect($result)->toBeNull();
        });

        test('getConnectedAccount returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->getConnectedAccount($user);

            expect($result)->toBeNull();
        });

        test('updateConnectedAccount returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->updateConnectedAccount($user);

            expect($result)->toBeNull();
        });

        test('updateConnectedAccount returns null with options', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->updateConnectedAccount($user, [
                'business_profile' => ['name' => 'Updated Business Name'],
            ]);

            expect($result)->toBeNull();
        });

        test('deleteConnectedAccount returns false', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->deleteConnectedAccount($user);

            expect($result)->toBeFalse();
        });

        test('getAccountOnboardingUrl returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->getAccountOnboardingUrl($user);

            expect($result)->toBeNull();
        });

        test('getAccountOnboardingUrl returns null with returnUrl and refreshUrl', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->getAccountOnboardingUrl(
                $user,
                'https://example.com/return',
                'https://example.com/refresh'
            );

            expect($result)->toBeNull();
        });

        test('isAccountOnboardingComplete returns false', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->isAccountOnboardingComplete($user);

            expect($result)->toBeFalse();
        });

        test('getAccountDashboardUrl returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->getAccountDashboardUrl($user);

            expect($result)->toBeNull();
        });
    });

    describe('Balance methods', function (): void {
        test('getBalance returns null', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->getBalance($user);

            expect($result)->toBeNull();
        });

        test('getPlatformBalance returns null', function (): void {
            $result = $this->driver->getPlatformBalance();

            expect($result)->toBeNull();
        });
    });

    describe('Payout methods', function (): void {
        test('createPayout returns null', function (): void {
            $seller = User::factory()->create();
            $payout = Payout::factory()->pending()->create(['seller_id' => $seller->id]);

            $result = $this->driver->createPayout($payout);

            expect($result)->toBeNull();
        });

        test('getPayout returns null', function (): void {
            $seller = User::factory()->create();
            $payout = Payout::factory()->pending()->create(['seller_id' => $seller->id]);

            $result = $this->driver->getPayout($payout);

            expect($result)->toBeNull();
        });

        test('cancelPayout returns false', function (): void {
            $seller = User::factory()->create();
            $payout = Payout::factory()->pending()->create(['seller_id' => $seller->id]);

            $result = $this->driver->cancelPayout($payout);

            expect($result)->toBeFalse();
        });

        test('retryPayout returns null', function (): void {
            $seller = User::factory()->create();
            $payout = Payout::factory()->failed()->create(['seller_id' => $seller->id]);

            $result = $this->driver->retryPayout($payout);

            expect($result)->toBeNull();
        });

        test('listPayouts returns empty collection', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->listPayouts($user);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });

        test('listPayouts returns empty collection with filters', function (): void {
            $user = User::factory()->create();

            $result = $this->driver->listPayouts($user, ['status' => 'pending', 'limit' => 10]);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });
    });

    describe('Transfer methods', function (): void {
        test('createTransfer returns null', function (): void {
            $recipient = User::factory()->create();

            $result = $this->driver->createTransfer($recipient, 100.00);

            expect($result)->toBeNull();
        });

        test('createTransfer returns null with metadata', function (): void {
            $recipient = User::factory()->create();

            $result = $this->driver->createTransfer($recipient, 250.00, [
                'order_id' => 'order_123',
                'description' => 'Product sale payout',
            ]);

            expect($result)->toBeNull();
        });

        test('getTransfer returns null', function (): void {
            $result = $this->driver->getTransfer('tr_123');

            expect($result)->toBeNull();
        });

        test('reverseTransfer returns null', function (): void {
            $result = $this->driver->reverseTransfer('tr_123');

            expect($result)->toBeNull();
        });
    });
});
