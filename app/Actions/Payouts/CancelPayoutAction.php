<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Enums\PayoutStatus;
use App\Events\PayoutCancelled;
use App\Exceptions\InvalidPayoutStatusException;
use App\Models\Payout;

class CancelPayoutAction
{
    /**
     * @throws InvalidPayoutStatusException
     */
    public function execute(Payout $payout, ?string $reason = null): bool
    {
        if (! $payout->canCancel()) {
            throw new InvalidPayoutStatusException("Payout cannot be cancelled. Only pending payouts can be cancelled. Current status: {$payout->status->value}");
        }

        $notesUpdate = $payout->notes;
        if ($reason) {
            $notesUpdate .= "\n\nCancellation reason: {$reason}";
        }

        $payout->update([
            'status' => PayoutStatus::Cancelled,
            'notes' => $notesUpdate,
        ]);

        event(new PayoutCancelled($payout, $reason));

        return true;
    }
}
