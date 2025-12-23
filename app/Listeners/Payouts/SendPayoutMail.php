<?php

declare(strict_types=1);

namespace App\Listeners\Payouts;

use App\Enums\PayoutStatus;
use App\Events\PayoutProcessed;
use App\Mail\Payouts\NewPayout;
use Illuminate\Support\Facades\Mail;

class SendPayoutMail
{
    public function handle(PayoutProcessed $event): void
    {
        if ($event->payout->status !== PayoutStatus::Completed) {
            return;
        }

        Mail::to($event->payout->seller->email)->send(new NewPayout(
            payout: $event->payout
        ));
    }
}
