<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Action;
use App\Data\CustomerData;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Jobs\Payments\SwapSubscription;
use App\Models\Price;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Throwable;

class CreateSwapSubscriptionsBatchAction extends Action
{
    public function __construct(
        protected Collection $users,
        protected Price $price,
        protected ProrationBehavior $prorationBehavior,
        protected PaymentBehavior $paymentBehavior,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): bool
    {
        $batch = Bus::batch([]);

        $this->users->each(fn (CustomerData|User $user) => $batch->add(new SwapSubscription(
            user: $user instanceof CustomerData ? User::find($user->id) : $user,
            price: $this->price,
            prorationBehavior: $this->prorationBehavior,
            paymentBehavior: $this->paymentBehavior,
        )));

        $batch->name('Swap User Subscriptions')->dispatch();

        return true;
    }
}
