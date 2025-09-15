<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreated;
use App\Models\Group;

class AssignDefaultMemberGroup
{
    public function handle(UserCreated $event): void
    {
        $groups = Group::query()->defaultMemberGroups()->first();

        if (blank($groups)) {
            return;
        }

        $groups->each(fn (Group $group) => $event->user->assignToGroup($group));
    }
}
