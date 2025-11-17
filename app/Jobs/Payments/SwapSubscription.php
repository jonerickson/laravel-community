<?php

declare(strict_types=1);

namespace App\Jobs\Payments;

use App\Actions\Payments\SwapSubscriptionAction;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Models\Price;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SwapSubscription implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(
        protected User $user,
        protected Price $price,
        protected ProrationBehavior $prorationBehavior,
        protected PaymentBehavior $paymentBehavior,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        SwapSubscriptionAction::execute($this->user, $this->price, $this->prorationBehavior, $this->paymentBehavior);
    }
}
