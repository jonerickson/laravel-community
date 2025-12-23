<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Actions\Action;
use App\Enums\PayoutStatus;
use App\Events\PayoutCancelled;
use App\Exceptions\InvalidPayoutStatusException;
use App\Models\Payout;

class CancelPayoutAction extends Action
{
    public function __construct(
        protected Payout $payout,
        protected ?string $reason = null,
    ) {
        //
    }

    /**
     * @throws InvalidPayoutStatusException
     */
    public function __invoke(): bool
    {
        if (! $this->payout->canCancel()) {
            throw new InvalidPayoutStatusException('The payout cannot be cancelled. Only pending payouts can be cancelled. Current status: '.$this->payout->status->value);
        }

        $notesUpdate = $this->payout->notes;
        if ($this->reason) {
            $notesUpdate .= '

Cancellation reason: '.$this->reason;
        }

        $this->payout->update([
            'status' => PayoutStatus::Cancelled,
            'notes' => $notesUpdate,
        ]);

        event(new PayoutCancelled($this->payout, $this->reason));

        return true;
    }
}
