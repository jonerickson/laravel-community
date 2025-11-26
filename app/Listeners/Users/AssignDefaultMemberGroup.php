<?php

declare(strict_types=1);

namespace App\Listeners\Users;

use App\Events\UserCreated;
use App\Models\Group;
use Illuminate\Support\Facades\App;

class AssignDefaultMemberGroup
{
    public function handle(UserCreated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if (! ($group = Group::defaultMemberGroup()) instanceof Group) {
            return;
        }

        $event->user->assignToGroup($group);
    }
}
