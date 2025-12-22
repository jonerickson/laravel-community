<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Exceptions\InsufficientBalanceException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeductPayoutFromBalanceAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $seller, float $amount): bool
    {
        return DB::transaction(function () use ($seller, $amount): bool {
            $seller = User::where('id', $seller->id)->lockForUpdate()->first();

            if ($seller->current_balance < $amount) {
                throw new InsufficientBalanceException(sprintf('Seller balance ($%s) is less than payout amount ($%s)', $seller->current_balance, $amount));
            }

            $newBalance = $seller->current_balance - $amount;

            $seller->update(['current_balance' => $newBalance]);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($seller)
                ->withProperties(['amount' => -$amount, 'reason' => 'payout_deduction', 'new_balance' => $newBalance])
                ->log('balance_updated');

            return true;
        });
    }
}
