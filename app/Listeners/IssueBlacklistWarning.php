<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Warnings\IssueWarningAction;
use App\Events\BlacklistMatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class IssueBlacklistWarning implements ShouldQueue
{
    use Queueable;

    /**
     * @throws Throwable
     */
    public function handle(BlacklistMatch $event): void
    {
        if (blank($event->user) || blank($event->blacklist->warning)) {
            return;
        }

        IssueWarningAction::execute(
            $event->user,
            $event->blacklist->warning,
            'This warning was automatically issued from the blacklist service.',
        );
    }
}
