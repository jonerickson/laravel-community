<?php

declare(strict_types=1);

namespace App\Listeners\Discord;

use App\Events\UserGroupCreated;
use App\Events\UserGroupDeleted;
use App\Jobs\Discord\SyncRoles as SyncRolesJob;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\App;

class SyncRoles
{
    public function handle(Login|UserGroupCreated|UserGroupDeleted $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $user = match ($event::class) {
            Login::class => $event->user,
            UserGroupCreated::class, UserGroupDeleted::class => $event->userGroup->user,
        };

        if (! $user instanceof User) {
            return;
        }

        SyncRolesJob::dispatch($user->id);
    }
}
