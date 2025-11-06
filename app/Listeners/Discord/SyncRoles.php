<?php

declare(strict_types=1);

namespace App\Listeners\Discord;

use App\Events\UserGroupCreated;
use App\Events\UserGroupDeleted;
use App\Jobs\Discord\SyncRoles as SyncRolesJob;
use Illuminate\Support\Facades\App;

class SyncRoles
{
    public function handle(UserGroupCreated|UserGroupDeleted $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if (! $user = $event->userGroup->user) {
            return;
        }

        SyncRolesJob::dispatch($user->id);
    }
}
