<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Actions\Action;
use App\Enums\PayoutStatus;
use App\Exceptions\InvalidPayoutStatusException;
use App\Models\Payout;
use Throwable;

class RetryPayoutAction extends Action
{
    public function __construct(
        protected Payout $payout,
    ) {
        //
    }

    /**
     * @throws InvalidPayoutStatusException|Throwable
     */
    public function __invoke(): bool
    {
        if (! $this->payout->canRetry()) {
            throw new InvalidPayoutStatusException('The payout cannot be retried. Only failed payouts can be retried. Current status: '.$this->payout->status->value);
        }

        $this->payout->update([
            'status' => PayoutStatus::Pending,
            'failure_reason' => null,
            'created_by' => null,
        ]);

        $processAction = app(ProcessPayoutAction::class);

        return $processAction->execute($this->payout);
    }
}
