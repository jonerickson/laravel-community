<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Enums\PayoutStatus;
use App\Exceptions\InvalidPayoutStatusException;
use App\Models\Payout;

class RetryPayoutAction
{
    /**
     * @throws InvalidPayoutStatusException
     */
    public function execute(Payout $payout): bool
    {
        if (! $payout->canRetry()) {
            throw new InvalidPayoutStatusException("Payout cannot be retried. Only failed payouts can be retried. Current status: {$payout->status->value}");
        }

        $payout->update([
            'status' => PayoutStatus::Pending,
            'failure_reason' => null,
            'processed_at' => null,
            'processed_by' => null,
        ]);

        $processAction = app(ProcessPayoutAction::class);

        return $processAction->execute($payout);
    }
}
