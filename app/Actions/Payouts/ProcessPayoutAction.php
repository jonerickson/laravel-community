<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Enums\PayoutStatus;
use App\Events\PayoutFailed;
use App\Events\PayoutProcessed;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidPayoutStatusException;
use App\Facades\PayoutProcessor;
use App\Models\Payout;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessPayoutAction
{
    /**
     * @throws Throwable
     */
    public function execute(Payout $payout): bool
    {
        return DB::transaction(function () use ($payout): bool {
            if ($payout->status !== PayoutStatus::Pending) {
                throw new InvalidPayoutStatusException('Payout must be in Pending status to be processed. Current status: '.$payout->status->value);
            }

            try {
                $deductAction = app(DeductPayoutFromBalanceAction::class);
                $deductAction->execute($payout->user, $payout->amount);
            } catch (InsufficientBalanceException $insufficientBalanceException) {
                $payout->update([
                    'status' => PayoutStatus::Failed,
                    'failure_reason' => $insufficientBalanceException->getMessage(),
                ]);

                event(new PayoutFailed($payout, $insufficientBalanceException->getMessage()));

                return false;
            }

            try {
                $result = PayoutProcessor::createPayout($payout);

                if ($result) {
                    $payout->update([
                        'status' => PayoutStatus::Completed,
                        'processed_at' => now(),
                        'processed_by' => Auth::id(),
                    ]);

                    event(new PayoutProcessed($payout));

                    return true;
                }

                $updateBalanceAction = app(UpdateSellerBalanceAction::class);
                $updateBalanceAction->execute($payout->user, $payout->amount, 'payout_failed_refund');

                $payout->update([
                    'status' => PayoutStatus::Failed,
                    'failure_reason' => 'Driver returned null - payout creation failed',
                ]);

                event(new PayoutFailed($payout, 'Driver error'));

                return false;

            } catch (Exception $exception) {
                $updateBalanceAction = app(UpdateSellerBalanceAction::class);
                $updateBalanceAction->execute($payout->user, $payout->amount, 'payout_exception_refund');

                $payout->update([
                    'status' => PayoutStatus::Failed,
                    'failure_reason' => $exception->getMessage(),
                ]);

                event(new PayoutFailed($payout, $exception->getMessage()));

                return false;
            }
        });
    }
}
