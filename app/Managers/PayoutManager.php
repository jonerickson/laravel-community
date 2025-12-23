<?php

declare(strict_types=1);

namespace App\Managers;

use App\Data\BalanceData;
use App\Data\ConnectedAccountData;
use App\Data\PayoutData;
use App\Data\TransferData;
use App\Drivers\Payouts\NullDriver;
use App\Drivers\Payouts\PayoutProcessor;
use App\Drivers\Payouts\StripeDriver;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class PayoutManager extends Manager implements PayoutProcessor
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('payout.default') ?? 'null';
    }

    public function createConnectedAccount(User $user, array $options = []): ?ConnectedAccountData
    {
        return $this->driver()->createConnectedAccount($user, $options);
    }

    public function getConnectedAccount(User $user): ?ConnectedAccountData
    {
        return $this->driver()->getConnectedAccount($user);
    }

    public function updateConnectedAccount(User $user, array $options = []): ?ConnectedAccountData
    {
        return $this->driver()->updateConnectedAccount($user, $options);
    }

    public function deleteConnectedAccount(User $user): bool
    {
        return $this->driver()->deleteConnectedAccount($user);
    }

    public function getAccountOnboardingUrl(User $user, ?string $returnUrl = null, ?string $refreshUrl = null): ?string
    {
        return $this->driver()->getAccountOnboardingUrl($user, $returnUrl, $refreshUrl);
    }

    public function isAccountOnboardingComplete(User $user): bool
    {
        return $this->driver()->isAccountOnboardingComplete($user);
    }

    public function getAccountDashboardUrl(User $user): ?string
    {
        return $this->driver()->getAccountDashboardUrl($user);
    }

    public function getBalance(User $user): ?BalanceData
    {
        return $this->driver()->getBalance($user);
    }

    public function getPlatformBalance(): ?BalanceData
    {
        return $this->driver()->getPlatformBalance();
    }

    public function createPayout(Payout $payout): ?PayoutData
    {
        return $this->driver()->createPayout($payout);
    }

    public function getPayout(Payout $payout): ?PayoutData
    {
        return $this->driver()->getPayout($payout);
    }

    public function cancelPayout(Payout $payout): bool
    {
        return $this->driver()->cancelPayout($payout);
    }

    public function retryPayout(Payout $payout): ?PayoutData
    {
        return $this->driver()->retryPayout($payout);
    }

    public function listPayouts(User $user, array $filters = []): ?Collection
    {
        return $this->driver()->listPayouts($user, $filters);
    }

    public function createTransfer(User $recipient, float $amount, array $metadata = []): ?TransferData
    {
        return $this->driver()->createTransfer($recipient, $amount, $metadata);
    }

    public function getTransfer(string $transferId): ?TransferData
    {
        return $this->driver()->getTransfer($transferId);
    }

    public function reverseTransfer(string $transferId): ?TransferData
    {
        return $this->driver()->reverseTransfer($transferId);
    }

    protected function createStripeDriver(): PayoutProcessor
    {
        $stripeSecret = $this->config->get('services.stripe.secret');

        if (blank($stripeSecret)) {
            throw new InvalidArgumentException('Stripe secret is not defined.');
        }

        return new StripeDriver(
            stripeSecret: $stripeSecret,
        );
    }

    protected function createNullDriver(): PayoutProcessor
    {
        return new NullDriver;
    }
}
