<?php

declare(strict_types=1);

namespace App\Actions\Payouts;

use App\Actions\Action;
use App\Enums\PayoutStatus;
use App\Events\PayoutCreated;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreatePayoutAction extends Action
{
    public function __construct(
        protected User $seller,
        protected float $amount,
        protected ?string $method = null,
        protected ?string $notes = null
    ) {
        //
    }

    /**
     * @throws InsufficientBalanceException
     */
    public function __invoke(): Payout
    {
        if ($this->amount < config('payout.minimum_payout')) {
            throw ValidationException::withMessages([
                'amount' => 'Payout amount must be at least $'.config('payout.minimum_payout'),
            ]);
        }

        if ($this->amount > config('payout.maximum_payout')) {
            throw ValidationException::withMessages([
                'amount' => 'Payout amount cannot exceed $'.config('payout.maximum_payout'),
            ]);
        }

        if ($this->seller->current_balance < $this->amount) {
            throw new InsufficientBalanceException(sprintf('Insufficient balance. Current balance: $%s, Requested: $%s', $this->seller->current_balance, $this->amount));
        }

        if (config('payout.default') === 'stripe' && ! $this->seller->isPayoutAccountOnboardingComplete()) {
            throw ValidationException::withMessages([
                'seller' => 'Seller must complete payout account onboarding before requesting a payout',
            ]);
        }

        $payout = Payout::create([
            'user_id' => $this->seller->id,
            'amount' => $this->amount,
            'status' => PayoutStatus::Pending,
            'payout_method' => $this->method ?? config('payout.default'),
            'notes' => $this->notes,
        ]);

        event(new PayoutCreated($payout));

        return $payout;
    }
}
