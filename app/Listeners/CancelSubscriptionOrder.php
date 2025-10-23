<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SubscriptionDeleted;
use App\Managers\PaymentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CancelSubscriptionOrder implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function __construct(
        private readonly PaymentManager $paymentManager,
    ) {
        //
    }

    public function handle(SubscriptionDeleted $event): void
    {
        $this->paymentManager->cancelOrder($event->order);
    }
}
