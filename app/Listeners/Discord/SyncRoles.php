<?php

declare(strict_types=1);

namespace App\Listeners\Discord;

use App\Events\UserGroupCreated;
use App\Events\UserGroupDeleted;
use App\Jobs\Discord\SyncRoles as SyncRolesJob;

class SyncRoles
{
    public function handle(UserGroupCreated|UserGroupDeleted $event): void
    {
        SyncRolesJob::dispatch($event->userGroup->user);
    }
}
