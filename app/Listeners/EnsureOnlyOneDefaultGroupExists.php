<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GroupSaving;
use Illuminate\Support\Facades\App;

class EnsureOnlyOneDefaultGroupExists
{
    public function handle(GroupSaving $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if ($event->group->is_default_guest) {
            $event->group->toggleDefaultGuestGroup();
        }

        if ($event->group->is_default_member) {
            $event->group->toggleDefaultMemberGroup();
        }
    }
}
