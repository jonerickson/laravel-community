<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Actions\Action;
use App\Data\PayoutData;
use App\Data\TransferData;
use App\Enums\PayoutStatus;
use App\Events\PayoutFailed;
use App\Events\PayoutProcessed;
use App\Exceptions\InvalidPayoutStatusException;
use App\Facades\PayoutProcessor;
use App\Models\Payout;
use Exception;
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
                throw new InvalidPayoutStatusException('A payout must be in pending status to be processed. Current status: '.$this->payout->status->value);
            }

            try {
                $transfer = PayoutProcessor::createTransfer($this->payout->seller, $this->payout->amount);

                if ($transfer instanceof TransferData) {
                    $result = PayoutProcessor::createPayout($this->payout);

                    if ($result instanceof PayoutData) {
                        $this->payout->update([
                            'status' => PayoutStatus::Completed,
                        ]);

                        event(new PayoutProcessed($this->payout));

                        return true;
                    }
                }

                $this->payout->update([
                    'status' => PayoutStatus::Failed,
                    'failure_reason' => 'Driver returned null - payout creation failed',
                ]);

                event(new PayoutFailed($this->payout, 'Driver error'));

                return false;

            } catch (Exception $exception) {
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
