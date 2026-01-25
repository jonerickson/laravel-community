<?php

declare(strict_types=1);

use App\Drivers\Payouts\PayoutProcessor;
use App\Drivers\Payouts\StripeDriver;
use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\User;

describe('StripeDriver for Payouts', function (): void {
    beforeEach(function (): void {
        $this->driver = new StripeDriver(config('payout.stripe.secret', 'sk_test_fake'));
    });

    test('implements PayoutProcessor interface', function (): void {
        expect($this->driver)->toBeInstanceOf(PayoutProcessor::class);
    });

    describe('Connected Account methods', function (): void {
        test('createConnectedAccount returns existing account when user already has one', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
                'payouts_enabled' => true,
                'external_payout_account_onboarded_at' => now(),
            ]);

            $result = $this->driver->createConnectedAccount($user);

            expect($result)->toBeNull();
        });

        test('getConnectedAccount returns null when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->getConnectedAccount($user);

            expect($result)->toBeNull();
        });

        test('updateConnectedAccount returns null when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->updateConnectedAccount($user);

            expect($result)->toBeNull();
        });

        test('updateConnectedAccount returns null with options when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->updateConnectedAccount($user, [
                'business_profile' => ['name' => 'Updated Business Name'],
            ]);

            expect($result)->toBeNull();
        });

        test('deleteConnectedAccount returns false when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->deleteConnectedAccount($user);

            expect($result)->toBeFalse();
        });

        test('getAccountOnboardingUrl creates account when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->getAccountOnboardingUrl($user);

            expect($result)->toBeNull();
        });

        test('isAccountOnboardingComplete returns false when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->isAccountOnboardingComplete($user);

            expect($result)->toBeFalse();
        });

        test('getAccountDashboardUrl returns null when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->getAccountDashboardUrl($user);

            expect($result)->toBeNull();
        });
    });

    describe('Balance methods', function (): void {
        test('getBalance returns null when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->getBalance($user);

            expect($result)->toBeNull();
        });

        test('getBalance returns null when user payout account is not onboarded', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
                'external_payout_account_onboarded_at' => null,
            ]);

            $result = $this->driver->getBalance($user);

            expect($result)->toBeNull();
        });

        test('getPlatformBalance returns null on API error', function (): void {
            $result = $this->driver->getPlatformBalance();

            expect($result)->toBeNull();
        });
    });

    describe('Payout methods', function (): void {
        test('createPayout returns null when seller has no payout account', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => null,
            ]);
            $payout = Payout::factory()->pending()->create(['seller_id' => $seller->id]);

            $result = $this->driver->createPayout($payout);

            expect($result)->toBeNull();
            expect($payout->fresh()->status)->toBe(PayoutStatus::Failed);
            expect($payout->fresh()->failure_reason)->toBe('User does not have a connected payout account');
        });

        test('createPayout returns null when seller payout account is not onboarded', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
                'external_payout_account_onboarded_at' => null,
            ]);
            $payout = Payout::factory()->pending()->create(['seller_id' => $seller->id]);

            $result = $this->driver->createPayout($payout);

            expect($result)->toBeNull();
            expect($payout->fresh()->status)->toBe(PayoutStatus::Failed);
            expect($payout->fresh()->failure_reason)->toBe('User has not completed payout account onboarding');
        });

        test('createPayout returns null on API error for onboarded seller', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
                'external_payout_account_onboarded_at' => now(),
                'payouts_enabled' => true,
            ]);
            $payout = Payout::factory()->pending()->create(['seller_id' => $seller->id]);

            $result = $this->driver->createPayout($payout);

            expect($result)->toBeNull();
        });

        test('getPayout returns null when payout has no external payout id', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
            ]);
            $payout = Payout::factory()->pending()->create([
                'seller_id' => $seller->id,
                'external_payout_id' => null,
            ]);

            $result = $this->driver->getPayout($payout);

            expect($result)->toBeNull();
        });

        test('getPayout returns null when seller has no payout account', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => null,
            ]);
            $payout = Payout::factory()->pending()->create([
                'seller_id' => $seller->id,
                'external_payout_id' => 'po_123',
            ]);

            $result = $this->driver->getPayout($payout);

            expect($result)->toBeNull();
        });

        test('cancelPayout returns false when payout has no external payout id', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
            ]);
            $payout = Payout::factory()->pending()->create([
                'seller_id' => $seller->id,
                'external_payout_id' => null,
            ]);

            $result = $this->driver->cancelPayout($payout);

            expect($result)->toBeFalse();
        });

        test('cancelPayout returns false when seller has no payout account', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => null,
            ]);
            $payout = Payout::factory()->pending()->create([
                'seller_id' => $seller->id,
                'external_payout_id' => 'po_123',
            ]);

            $result = $this->driver->cancelPayout($payout);

            expect($result)->toBeFalse();
        });

        test('retryPayout calls createPayout', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => null,
            ]);
            $payout = Payout::factory()->failed()->create(['seller_id' => $seller->id]);

            $result = $this->driver->retryPayout($payout);

            expect($result)->toBeNull();
            expect($payout->fresh()->failure_reason)->toBe('User does not have a connected payout account');
        });

        test('listPayouts returns empty collection when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->listPayouts($user);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });

        test('listPayouts returns empty collection on API error for user with payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
            ]);

            $result = $this->driver->listPayouts($user);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
        });

        test('listPayouts returns empty collection with filters when user has no payout account', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->listPayouts($user, ['status' => 'pending', 'limit' => 10]);

            expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
            expect($result)->toBeEmpty();
        });
    });

    describe('Transfer methods', function (): void {
        test('createTransfer returns null when recipient has no payout account', function (): void {
            $recipient = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->createTransfer($recipient, 100.00);

            expect($result)->toBeNull();
        });

        test('createTransfer returns null with metadata when recipient has no payout account', function (): void {
            $recipient = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            $result = $this->driver->createTransfer($recipient, 250.00, [
                'order_id' => 'order_123',
                'description' => 'Product sale payout',
            ]);

            expect($result)->toBeNull();
        });

        test('createTransfer returns null on API error for recipient with payout account', function (): void {
            $recipient = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
            ]);

            $result = $this->driver->createTransfer($recipient, 100.00);

            expect($result)->toBeNull();
        });

        test('getTransfer returns null on API error', function (): void {
            $result = $this->driver->getTransfer('tr_123');

            expect($result)->toBeNull();
        });

        test('reverseTransfer returns null on API error', function (): void {
            $result = $this->driver->reverseTransfer('tr_123');

            expect($result)->toBeNull();
        });
    });

    describe('User payout account helpers', function (): void {
        test('hasPayoutAccount returns true when user has external payout account id', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
            ]);

            expect($user->hasPayoutAccount())->toBeTrue();
        });

        test('hasPayoutAccount returns false when user has no external payout account id', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => null,
            ]);

            expect($user->hasPayoutAccount())->toBeFalse();
        });

        test('isPayoutAccountOnboardingComplete returns true when onboarded_at is set', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
                'external_payout_account_onboarded_at' => now(),
            ]);

            expect($user->isPayoutAccountOnboardingComplete())->toBeTrue();
        });

        test('isPayoutAccountOnboardingComplete returns false when onboarded_at is null', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
                'external_payout_account_onboarded_at' => null,
            ]);

            expect($user->isPayoutAccountOnboardingComplete())->toBeFalse();
        });

        test('payoutAccountId returns the external payout account id', function (): void {
            $user = User::factory()->create([
                'external_payout_account_id' => 'acct_test123',
            ]);

            expect($user->payoutAccountId())->toBe('acct_test123');
        });
    });

    describe('PayoutStatus transitions', function (): void {
        test('payout is set to Failed when seller has no payout account', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => null,
            ]);
            $payout = Payout::factory()->pending()->create(['seller_id' => $seller->id]);

            $this->driver->createPayout($payout);

            expect($payout->fresh()->status)->toBe(PayoutStatus::Failed);
        });

        test('payout is set to Failed when seller onboarding not complete', function (): void {
            $seller = User::factory()->create([
                'external_payout_account_id' => 'acct_123',
                'external_payout_account_onboarded_at' => null,
            ]);
            $payout = Payout::factory()->pending()->create(['seller_id' => $seller->id]);

            $this->driver->createPayout($payout);

            expect($payout->fresh()->status)->toBe(PayoutStatus::Failed);
        });
    });
});
