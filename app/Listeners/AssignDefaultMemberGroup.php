<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreated;
use App\Models\Group;

class AssignDefaultMemberGroup
{
    public function handle(UserCreated $event): void
    {
        $group = Group::query()->where('is_default_member', true)->first();

        if (blank($group)) {
            return;
        }

        $event->user->assignToGroup($group);
    }
}
