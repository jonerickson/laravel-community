<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\FingerprintCreated;
use App\Events\FingerprintUpdated;
use App\Jobs\CheckFingerprintForFraud;

class CheckFingerprintForFraudListener
{
    public function handle(FingerprintCreated|FingerprintUpdated $event): void
    {
        $shouldDispatch = ! $event->fingerprint->is_banned
            && filled($event->fingerprint->request_id)
            && (blank($event->fingerprint->last_checked_at)
            || $event->fingerprint->last_checked_at->isBefore(now()->subDay()));

        CheckFingerprintForFraud::dispatchIf($shouldDispatch, $event->fingerprint);
    }
}
