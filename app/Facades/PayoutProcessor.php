<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getDefaultDriver()
 * @method static \App\Data\ConnectedAccountData|null createConnectedAccount(\App\Models\User $user, array $options = [])
 * @method static \App\Data\ConnectedAccountData|null getConnectedAccount(\App\Models\User $user)
 * @method static \App\Data\ConnectedAccountData|null updateConnectedAccount(\App\Models\User $user, array $options = [])
 * @method static bool deleteConnectedAccount(\App\Models\User $user)
 * @method static string|null getAccountOnboardingUrl(\App\Models\User $user, string|null $returnUrl = null, string|null $refreshUrl = null)
 * @method static bool isAccountOnboardingComplete(\App\Models\User $user)
 * @method static string|null getAccountDashboardUrl(\App\Models\User $user)
 * @method static \App\Data\BalanceData|null getBalance(\App\Models\User $user)
 * @method static \App\Data\BalanceData|null getPlatformBalance()
 * @method static \App\Data\PayoutData|null createPayout(\App\Models\Payout $payout)
 * @method static \App\Data\PayoutData|null getPayout(\App\Models\Payout $payout)
 * @method static bool cancelPayout(\App\Models\Payout $payout)
 * @method static \App\Data\PayoutData|null retryPayout(\App\Models\Payout $payout)
 * @method static \Illuminate\Support\Collection|null listPayouts(\App\Models\User $user, array $filters = [])
 * @method static \App\Data\TransferData|null createTransfer(\App\Models\User $recipient, float $amount, array $metadata = [])
 * @method static \App\Data\TransferData|null getTransfer(string $transferId)
 * @method static \App\Data\TransferData|null reverseTransfer(string $transferId)
 * @method static mixed driver(string|null $driver = null)
 * @method static \App\Managers\PayoutManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \App\Managers\PayoutManager setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \App\Managers\PayoutManager forgetDrivers()
 *
 * @see \App\Managers\PayoutManager
 */
class PayoutProcessor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'payout-processor';
    }
}
