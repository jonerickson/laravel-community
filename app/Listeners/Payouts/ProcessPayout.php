<?php

declare(strict_types=1);

namespace App\Listeners\Payouts;

use App\Actions\Payouts\ProcessPayoutAction;
use App\Events\PayoutCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class ProcessPayout implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    /**
     * @throws Throwable
     */
    public function handle(PayoutCreated $event): void
    {
        ProcessPayoutAction::execute($event->payout);
    }
}
