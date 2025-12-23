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
use Illuminate\Support\Str;
use Stripe\Account;
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

            $accountType = config('payout.stripe.connect_type', Account::TYPE_EXPRESS);
            $businessType = $options['business_type'] ?? Account::BUSINESS_TYPE_INDIVIDUAL;

            $account = $this->stripe->accounts->create([
                'type' => $accountType,
                'email' => $user->email,
                'business_type' => $businessType,
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
                'metadata' => [
                    'seller_id' => $user->reference_id,
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

            return ConnectedAccountData::from($account);
        });
    }

    public function getConnectedAccount(User $user): ?ConnectedAccountData
    {
        if (! $user->hasPayoutAccount()) {
            return null;
        }

        return $this->executeWithErrorHandling('getConnectedAccount', function () use ($user): ConnectedAccountData {
            $account = $this->stripe->accounts->retrieve($user->payoutAccountId());

            return ConnectedAccountData::from($account);
        });
    }

    public function updateConnectedAccount(User $user, array $options = []): ?ConnectedAccountData
    {
        if (! $user->hasPayoutAccount()) {
            return null;
        }

        return $this->executeWithErrorHandling('updateConnectedAccount', function () use ($user, $options): ConnectedAccountData {
            $account = $this->stripe->accounts->update($user->payoutAccountId(), $options);

            return ConnectedAccountData::from($account);
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
                'refresh_url' => $refreshUrl ?? route('marketplace.stripe-connect.refresh'),
                'return_url' => $returnUrl ?? route('marketplace.stripe-connect.return'),
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

            return BalanceData::from($balance);
        });
    }

    public function getPlatformBalance(): ?BalanceData
    {
        return $this->executeWithErrorHandling('getPlatformBalance', function (): BalanceData {
            $balance = $this->stripe->balance->retrieve();

            return BalanceData::from($balance);
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
                'statement_descriptor' => Str::of(config('payout.statement_descriptor'))
                    ->upper()
                    ->limit(22, '')
                    ->toString(),
                'metadata' => [
                    'payout_id' => $payout->reference_id,
                    'seller_id' => $user->reference_id,
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

            if (empty($payouts->data)) {
                return collect();
            }

            return PayoutData::collect($payouts->data);
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

            return TransferData::from($transfer);
        });
    }

    public function getTransfer(string $transferId): ?TransferData
    {
        return $this->executeWithErrorHandling('getTransfer', function () use ($transferId): TransferData {
            $transfer = $this->stripe->transfers->retrieve($transferId);

            return TransferData::from($transfer);
        });
    }

    public function reverseTransfer(string $transferId): ?TransferData
    {
        return $this->executeWithErrorHandling('reverseTransfer', function () use ($transferId): TransferData {
            $this->stripe->transfers->createReversal($transferId);
            $transfer = $this->stripe->transfers->retrieve($transferId);

            return TransferData::from($transfer);
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
