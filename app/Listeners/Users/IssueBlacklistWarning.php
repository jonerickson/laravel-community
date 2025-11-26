<?php

declare(strict_types=1);

namespace App\Listeners\Users;

use App\Actions\Warnings\IssueWarningAction;
use App\Events\BlacklistMatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Throwable;

class IssueBlacklistWarning implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    /**
     * @throws Throwable
     */
    public function handle(BlacklistMatch $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

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
