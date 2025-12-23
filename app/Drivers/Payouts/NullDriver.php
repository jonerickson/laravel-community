<?php

declare(strict_types=1);

namespace App\Drivers\Payouts;

use App\Data\BalanceData;
use App\Data\ConnectedAccountData;
use App\Data\PayoutData;
use App\Data\TransferData;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Support\Collection;

class NullDriver implements PayoutProcessor
{
    public function createConnectedAccount(User $user, array $options = []): ?ConnectedAccountData
    {
        return null;
    }

    public function getConnectedAccount(User $user): ?ConnectedAccountData
    {
        return null;
    }

    public function updateConnectedAccount(User $user, array $options = []): ?ConnectedAccountData
    {
        return null;
    }

    public function deleteConnectedAccount(User $user): bool
    {
        return false;
    }

    public function getAccountOnboardingUrl(User $user, ?string $returnUrl = null, ?string $refreshUrl = null): ?string
    {
        return null;
    }

    public function isAccountOnboardingComplete(User $user): bool
    {
        return false;
    }

    public function getAccountDashboardUrl(User $user): ?string
    {
        return null;
    }

    public function getBalance(User $user): ?BalanceData
    {
        return null;
    }

    public function getPlatformBalance(): ?BalanceData
    {
        return null;
    }

    public function createPayout(Payout $payout): ?PayoutData
    {
        return null;
    }

    public function getPayout(Payout $payout): ?PayoutData
    {
        return null;
    }

    public function cancelPayout(Payout $payout): bool
    {
        return false;
    }

    public function retryPayout(Payout $payout): ?PayoutData
    {
        return null;
    }

    public function listPayouts(User $user, array $filters = []): ?Collection
    {
        return collect();
    }

    public function createTransfer(User $recipient, float $amount, array $metadata = []): ?TransferData
    {
        return null;
    }

    public function getTransfer(string $transferId): ?TransferData
    {
        return null;
    }

    public function reverseTransfer(string $transferId): ?TransferData
    {
        return null;
    }
}
