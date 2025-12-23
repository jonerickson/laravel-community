<?php

declare(strict_types=1);

namespace App\Drivers\Payouts;

use App\Data\BalanceData;
use App\Data\ConnectedAccountData;
use App\Data\PayoutData;
use App\Data\TransferData;
use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\RateLimitException;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeDriver implements PayoutProcessor
{
    protected StripeClient $stripe;

    public function __construct(private readonly string $stripeSecret)
    {
        Stripe::setApiKey($this->stripeSecret);
        $this->stripe = new StripeClient($this->stripeSecret);
    }

    public function createConnectedAccount(User $user, array $options = []): ?ConnectedAccountData
    {
        return $this->executeWithErrorHandling('createConnectedAccount', function () use ($user, $options): ?ConnectedAccountData {
            if ($user->hasPayoutAccount()) {
                return $this->getConnectedAccount($user);
            }

            $accountType = config('payout.stripe.connect_type', 'express');

            $account = $this->stripe->accounts->create([
                'type' => $accountType,
                'email' => $user->email,
                'business_type' => $options['business_type'] ?? 'individual',
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
                'metadata' => [
                    'seller_id' => $user->id,
                    'seller_email' => $user->email,
                ],
            ]);

            DB::transaction(function () use ($user, $account): void {
                $user->update([
                    'payouts_enabled' => $account->details_submitted && $account->charges_enabled && $account->payouts_enabled,
                    'external_payout_account_id' => $account->id,
                    'external_payout_account_onboarded_at' => $account->details_submitted ? now() : null,
                    'external_payout_account_capabilities' => $account->capabilities?->toArray() ?? [],
                ]);
            });

            return ConnectedAccountData::from([
                'id' => $account->id,
                'email' => $account->email,
                'business_name' => $account->business_profile?->name,
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
                'capabilities' => $account->capabilities?->toArray(),
                'requirements' => $account->requirements?->toArray(),
                'country' => $account->country,
                'default_currency' => $account->default_currency,
            ]);
        });
    }

    public function getConnectedAccount(User $user): ?ConnectedAccountData
    {
        if (! $user->hasPayoutAccount()) {
            return null;
        }

        return $this->executeWithErrorHandling('getConnectedAccount', function () use ($user): ConnectedAccountData {
            $account = $this->stripe->accounts->retrieve($user->payoutAccountId());

            return ConnectedAccountData::from([
                'id' => $account->id,
                'email' => $account->email,
                'business_name' => $account->business_profile?->name,
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
                'capabilities' => $account->capabilities?->toArray(),
                'requirements' => $account->requirements?->toArray(),
                'country' => $account->country,
                'default_currency' => $account->default_currency,
            ]);
        });
    }

    public function updateConnectedAccount(User $user, array $options = []): ?ConnectedAccountData
    {
        if (! $user->hasPayoutAccount()) {
            return null;
        }

        return $this->executeWithErrorHandling('updateConnectedAccount', function () use ($user, $options): ConnectedAccountData {
            $account = $this->stripe->accounts->update($user->payoutAccountId(), $options);

            return ConnectedAccountData::from([
                'id' => $account->id,
                'email' => $account->email,
                'business_name' => $account->business_profile?->name,
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
                'capabilities' => $account->capabilities?->toArray(),
                'requirements' => $account->requirements?->toArray(),
                'country' => $account->country,
                'default_currency' => $account->default_currency,
            ]);
        });
    }

    public function deleteConnectedAccount(User $user): bool
    {
        if (! $user->hasPayoutAccount()) {
            return false;
        }

        return $this->executeWithErrorHandling('deleteConnectedAccount', function () use ($user): bool {
            $this->stripe->accounts->delete($user->payoutAccountId());

            DB::transaction(function () use ($user): void {
                $user->update([
                    'payouts_enabled' => false,
                    'external_payout_account_id' => null,
                    'external_payout_account_onboarded_at' => null,
                    'external_payout_account_capabilities' => null,
                ]);
            });

            return true;
        }, false);
    }

    public function getAccountOnboardingUrl(User $user, ?string $returnUrl = null, ?string $refreshUrl = null): ?string
    {
        if (! $user->hasPayoutAccount()) {
            $this->createConnectedAccount($user);
        }

        return $this->executeWithErrorHandling('getAccountOnboardingUrl', function () use ($user, $returnUrl, $refreshUrl): ?string {
            $accountLink = $this->stripe->accountLinks->create([
                'account' => $user->payoutAccountId(),
                'refresh_url' => $refreshUrl ?? config('payout.stripe.onboarding_refresh_url') ?? route('marketplace.stripe-connect.refresh'),
                'return_url' => $returnUrl ?? config('payout.stripe.onboarding_return_url') ?? route('marketplace.stripe-connect.return'),
                'type' => 'account_onboarding',
            ]);

            return $accountLink->url;
        });
    }

    public function isAccountOnboardingComplete(User $user): bool
    {
        if (! $user->hasPayoutAccount()) {
            return false;
        }

        return $this->executeWithErrorHandling('isAccountOnboardingComplete', function () use ($user): bool {
            $account = $this->stripe->accounts->retrieve($user->payoutAccountId());

            $isComplete = $account->details_submitted && $account->charges_enabled && $account->payouts_enabled;

            if ($user->isPayoutAccountOnboardingComplete() !== $isComplete) {
                DB::transaction(function () use ($user, $account, $isComplete): void {
                    $user->update([
                        'payouts_enabled' => $isComplete,
                        'external_payout_account_onboarded_at' => $isComplete ? now() : null,
                        'external_payout_account_capabilities' => $account->capabilities?->toArray() ?? [],
                    ]);
                });
            }

            return $isComplete;
        }, false);
    }

    public function getAccountDashboardUrl(User $user): ?string
    {
        if (! $user->hasPayoutAccount()) {
            return null;
        }

        return $this->executeWithErrorHandling('getAccountDashboardUrl', function () use ($user): ?string {
            $loginLink = $this->stripe->accounts->createLoginLink($user->payoutAccountId());

            return $loginLink->url;
        });
    }

    public function getBalance(User $user): ?BalanceData
    {
        if (! $user->hasPayoutAccount() || ! $user->isPayoutAccountOnboardingComplete()) {
            return null;
        }

        return $this->executeWithErrorHandling('getBalance', function () use ($user): BalanceData {
            $balance = $this->stripe->balance->retrieve([], ['stripe_account' => $user->payoutAccountId()]);

            $available = 0.0;
            $pending = 0.0;

            foreach ($balance->available as $item) {
                $available += $item->amount / 100;
            }

            foreach ($balance->pending as $item) {
                $pending += $item->amount / 100;
            }

            return BalanceData::from([
                'available' => $available,
                'pending' => $pending,
                'currency' => $balance->available[0]->currency ?? 'usd',
                'breakdown' => [
                    'available' => $balance->available,
                    'pending' => $balance->pending,
                ],
            ]);
        });
    }

    public function getPlatformBalance(): ?BalanceData
    {
        return $this->executeWithErrorHandling('getPlatformBalance', function (): BalanceData {
            $balance = $this->stripe->balance->retrieve();

            $available = 0.0;
            $pending = 0.0;

            foreach ($balance->available as $item) {
                $available += $item->amount / 100;
            }

            foreach ($balance->pending as $item) {
                $pending += $item->amount / 100;
            }

            return BalanceData::from([
                'available' => $available,
                'pending' => $pending,
                'currency' => $balance->available[0]->currency ?? 'usd',
                'breakdown' => [
                    'available' => $balance->available,
                    'pending' => $balance->pending,
                ],
            ]);
        });
    }

    public function createPayout(Payout $payout): ?PayoutData
    {
        $user = $payout->seller;

        if (! $user->hasPayoutAccount()) {
            $payout->update([
                'status' => PayoutStatus::Failed,
                'failure_reason' => 'User does not have a connected payout account',
            ]);

            return null;
        }

        if (! $user->isPayoutAccountOnboardingComplete()) {
            $payout->update([
                'status' => PayoutStatus::Failed,
                'failure_reason' => 'User has not completed payout account onboarding',
            ]);

            return null;
        }

        return $this->executeWithErrorHandling('createPayout', function () use ($payout, $user): PayoutData {
            $stripePayout = $this->stripe->payouts->create([
                'amount' => (int) ($payout->amount * 100),
                'currency' => 'usd',
                'metadata' => [
                    'payout_id' => $payout->id,
                    'seller_id' => $user->id,
                ],
            ], ['stripe_account' => $user->payoutAccountId()]);

            DB::transaction(function () use ($payout, $stripePayout): void {
                $payout->update([
                    'external_payout_id' => $stripePayout->id,
                    'status' => match ($stripePayout->status) {
                        'paid' => PayoutStatus::Completed,
                        'failed', 'canceled' => PayoutStatus::Failed,
                        default => PayoutStatus::Pending,
                    },
                ]);
            });

            return PayoutData::from($payout->fresh());
        });
    }

    public function getPayout(Payout $payout): ?PayoutData
    {
        if (blank($payout->external_payout_id)) {
            return null;
        }

        $user = $payout->seller;

        if (! $user->hasPayoutAccount()) {
            return null;
        }

        return $this->executeWithErrorHandling('getPayout', function () use ($payout, $user): PayoutData {
            $stripePayout = $this->stripe->payouts->retrieve(
                $payout->external_payout_id,
                [],
                ['stripe_account' => $user->payoutAccountId()]
            );

            DB::transaction(function () use ($payout, $stripePayout): void {
                $payout->update([
                    'status' => match ($stripePayout->status) {
                        'paid' => PayoutStatus::Completed,
                        'failed', 'canceled' => PayoutStatus::Failed,
                        default => PayoutStatus::Pending,
                    },
                    'failure_reason' => $stripePayout->failure_message ?? $payout->failure_reason,
                ]);
            });

            return PayoutData::from($payout->fresh());
        });
    }

    public function cancelPayout(Payout $payout): bool
    {
        if (blank($payout->external_payout_id)) {
            return false;
        }

        $user = $payout->seller;

        if (! $user->hasPayoutAccount()) {
            return false;
        }

        return $this->executeWithErrorHandling('cancelPayout', function () use ($payout, $user): bool {
            $this->stripe->payouts->cancel(
                $payout->external_payout_id,
                [],
                ['stripe_account' => $user->payoutAccountId()]
            );

            DB::transaction(function () use ($payout): void {
                $payout->update([
                    'status' => PayoutStatus::Cancelled,
                ]);
            });

            return true;
        }, false);
    }

    public function retryPayout(Payout $payout): ?PayoutData
    {
        return $this->createPayout($payout);
    }

    public function listPayouts(User $user, array $filters = []): ?Collection
    {
        if (! $user->hasPayoutAccount()) {
            return collect();
        }

        return $this->executeWithErrorHandling('listPayouts', function () use ($user, $filters): Collection {
            $payouts = $this->stripe->payouts->all(
                array_merge(['limit' => 100], $filters),
                ['stripe_account' => $user->payoutAccountId()]
            );

            return collect($payouts->data)->map(fn ($stripePayout): array => [
                'id' => $stripePayout->id,
                'amount' => $stripePayout->amount / 100,
                'currency' => $stripePayout->currency,
                'status' => $stripePayout->status,
                'arrival_date' => $stripePayout->arrival_date,
                'created' => $stripePayout->created,
                'method' => $stripePayout->method,
                'description' => $stripePayout->description,
            ]);
        }, collect());
    }

    public function createTransfer(User $recipient, float $amount, array $metadata = []): ?TransferData
    {
        if (! $recipient->hasPayoutAccount()) {
            return null;
        }

        return $this->executeWithErrorHandling('createTransfer', function () use ($recipient, $amount, $metadata): TransferData {
            $transfer = $this->stripe->transfers->create([
                'amount' => (int) ($amount * 100),
                'currency' => 'usd',
                'destination' => $recipient->payoutAccountId(),
                'metadata' => $metadata,
            ]);

            return TransferData::from([
                'id' => $transfer->id,
                'amount' => $transfer->amount / 100,
                'currency' => $transfer->currency,
                'destination' => $transfer->destination,
                'source_transaction' => $transfer->source_transaction,
                'metadata' => $transfer->metadata?->toArray(),
                'reversed' => $transfer->reversed,
                'created_at' => $transfer->created ? now()->setTimestamp($transfer->created) : null,
            ]);
        });
    }

    public function getTransfer(string $transferId): ?TransferData
    {
        return $this->executeWithErrorHandling('getTransfer', function () use ($transferId): TransferData {
            $transfer = $this->stripe->transfers->retrieve($transferId);

            return TransferData::from([
                'id' => $transfer->id,
                'amount' => $transfer->amount / 100,
                'currency' => $transfer->currency,
                'destination' => $transfer->destination,
                'source_transaction' => $transfer->source_transaction,
                'metadata' => $transfer->metadata?->toArray(),
                'reversed' => $transfer->reversed,
                'created_at' => $transfer->created ? now()->setTimestamp($transfer->created) : null,
            ]);
        });
    }

    public function reverseTransfer(string $transferId): ?TransferData
    {
        return $this->executeWithErrorHandling('reverseTransfer', function () use ($transferId): TransferData {
            $reversal = $this->stripe->transfers->createReversal($transferId);
            $transfer = $this->stripe->transfers->retrieve($transferId);

            return TransferData::from([
                'id' => $transfer->id,
                'amount' => $transfer->amount / 100,
                'currency' => $transfer->currency,
                'destination' => $transfer->destination,
                'source_transaction' => $transfer->source_transaction,
                'metadata' => $transfer->metadata?->toArray(),
                'reversed' => $transfer->reversed,
                'created_at' => $transfer->created ? now()->setTimestamp($transfer->created) : null,
            ]);
        });
    }

    private function executeWithErrorHandling(string $method, callable $callback, mixed $defaultValue = null): mixed
    {
        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                return $callback();
            } catch (RateLimitException $e) {
                $retryCount++;

                if ($retryCount >= $maxRetries) {
                    Log::error('Stripe payout rate limit exceeded for method '.$method, [
                        'method' => $method,
                        'error' => $e->getMessage(),
                        'retry_count' => $retryCount,
                    ]);

                    return $defaultValue;
                }

                $waitTime = min(2 ** $retryCount * 100000, 1000000);
                usleep($waitTime);
            } catch (ApiErrorException $e) {
                Log::error('Stripe payout API error for method '.$method, [
                    'method' => $method,
                    'error' => $e->getMessage(),
                    'stripe_code' => $e->getStripeCode(),
                ]);

                return $defaultValue;
            } catch (Exception $e) {
                Log::error('Stripe payout general error for method '.$method, [
                    'method' => $method,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return $defaultValue;
            }
        }

        return $defaultValue;
    }
}
