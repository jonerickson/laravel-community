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

interface PayoutProcessor
{
    public function createConnectedAccount(User $user, array $options = []): ?ConnectedAccountData;

    public function getConnectedAccount(User $user): ?ConnectedAccountData;

    public function updateConnectedAccount(User $user, array $options = []): ?ConnectedAccountData;

    public function deleteConnectedAccount(User $user): bool;

    public function getAccountOnboardingUrl(User $user, ?string $returnUrl = null, ?string $refreshUrl = null): ?string;

    public function isAccountOnboardingComplete(User $user): bool;

    public function getAccountDashboardUrl(User $user): ?string;

    public function getBalance(User $user): ?BalanceData;

    public function getPlatformBalance(): ?BalanceData;

    public function createPayout(Payout $payout): ?PayoutData;

    public function getPayout(Payout $payout): ?PayoutData;

    public function cancelPayout(Payout $payout): bool;

    public function retryPayout(Payout $payout): ?PayoutData;

    public function listPayouts(User $user, array $filters = []): ?Collection;

    public function createTransfer(User $recipient, float $amount, array $metadata = []): ?TransferData;

    public function getTransfer(string $transferId): ?TransferData;

    public function reverseTransfer(string $transferId): ?TransferData;
}
