<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateSellerBalanceAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $seller, float $amount, string $reason): bool
    {
        return DB::transaction(function () use ($seller, $amount, $reason): bool {
            $seller = User::where('id', $seller->id)->lockForUpdate()->first();

            $newBalance = $seller->current_balance + $amount;

            $seller->update(['current_balance' => $newBalance]);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($seller)
                ->withProperties(['amount' => $amount, 'reason' => $reason, 'new_balance' => $newBalance])
                ->log('balance_updated');

            return true;
        });
    }
}
