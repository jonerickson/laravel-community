<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Actions\Action;
use App\Enums\PayoutStatus;
use App\Events\PayoutFailed;
use App\Events\PayoutProcessed;
use App\Exceptions\InvalidPayoutStatusException;
use App\Facades\PayoutProcessor;
use App\Models\Payout;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessPayoutAction extends Action
{
    public function __construct(
        protected Payout $payout,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): bool
    {
        return DB::transaction(function (): bool {
            if ($this->payout->status !== PayoutStatus::Pending) {
                throw new InvalidPayoutStatusException('Payout must be in Pending status to be processed. Current status: '.$this->payout->status->value);
            }

            try {
                $result = PayoutProcessor::createPayout($this->payout);

                if ($result) {
                    $this->payout->update([
                        'status' => PayoutStatus::Completed,
                        'processed_at' => now(),
                        'processed_by' => Auth::id(),
                    ]);

                    event(new PayoutProcessed($this->payout));

                    return true;
                }

                //                $updateBalanceAction = app(UpdateSellerBalanceAction::class);
                //                $updateBalanceAction->execute($payout->user, $payout->amount, 'payout_failed_refund');

                $this->payout->update([
                    'status' => PayoutStatus::Failed,
                    'failure_reason' => 'Driver returned null - payout creation failed',
                ]);

                event(new PayoutFailed($this->payout, 'Driver error'));

                return false;

            } catch (Exception $exception) {
                //                $updateBalanceAction = app(UpdateSellerBalanceAction::class);
                //                $updateBalanceAction->execute($payout->user, $payout->amount, 'payout_exception_refund');

                $this->payout->update([
                    'status' => PayoutStatus::Failed,
                    'failure_reason' => $exception->getMessage(),
                ]);

                event(new PayoutFailed($this->payout, $exception->getMessage()));

                return false;
            }
        });
    }
}
