<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GroupSaving;

class EnsureOnlyOneDefaultGroupExists
{
    public function handle(GroupSaving $event): void
    {
        if ($event->group->is_default_guest) {
            $event->group->toggleDefaultGuestGroup();
        }

        if ($event->group->is_default_member) {
            $event->group->toggleDefaultMemberGroup();
        }
    }
}
