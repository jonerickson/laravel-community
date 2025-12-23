<?php

declare(strict_types=1);

namespace App\Listeners\Payouts;

use App\Enums\CommissionStatus;
use App\Events\PayoutCancelled;
use App\Events\PayoutFailed;
use App\Models\Commission;

class ResetCommissionStatuses
{
    public function handle(PayoutCancelled|PayoutFailed $event): void
    {
        $event->payout->commissions->each(function (Commission $commission): void {
            $commission->update([
                'status' => CommissionStatus::Pending,
            ]);
        });
    }
}
