<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Enums\PayoutStatus;
use App\Events\PayoutCreated;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreatePayoutAction
{
    /**
     * @throws InsufficientBalanceException
     */
    public function execute(User $seller, float $amount, ?string $method = null, ?string $notes = null): Payout
    {
        if ($amount < config('payout.minimum_payout')) {
            throw ValidationException::withMessages([
                'amount' => 'Payout amount must be at least $'.config('payout.minimum_payout'),
            ]);
        }

        if ($amount > config('payout.maximum_payout')) {
            throw ValidationException::withMessages([
                'amount' => 'Payout amount cannot exceed $'.config('payout.maximum_payout'),
            ]);
        }

        if ($seller->current_balance < $amount) {
            throw new InsufficientBalanceException(sprintf('Insufficient balance. Current balance: $%s, Requested: $%s', $seller->current_balance, $amount));
        }

        if (config('payout.default') === 'stripe' && ! $seller->isPayoutAccountOnboardingComplete()) {
            throw ValidationException::withMessages([
                'seller' => 'Seller must complete payout account onboarding before requesting a payout',
            ]);
        }

        $payout = Payout::create([
            'user_id' => $seller->id,
            'amount' => $amount,
            'status' => PayoutStatus::Pending,
            'payout_method' => $method ?? config('payout.default'),
            'notes' => $notes,
        ]);

        event(new PayoutCreated($payout));

        return $payout;
    }
}
