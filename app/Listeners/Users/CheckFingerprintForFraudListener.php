<?php

declare(strict_types=1);

namespace App\Listeners\Users;

use App\Events\FingerprintCreated;
use App\Events\FingerprintUpdated;
use App\Jobs\Users\CheckFingerprintForFraud;
use Illuminate\Support\Facades\App;

class CheckFingerprintForFraudListener
{
    public function handle(FingerprintCreated|FingerprintUpdated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $shouldDispatch = filled($event->fingerprint->request_id)
            && (blank($event->fingerprint->last_checked_at)
            || $event->fingerprint->last_checked_at->isBefore(now()->subDay()));

        CheckFingerprintForFraud::dispatchIf($shouldDispatch, $event->fingerprint);
    }
}
