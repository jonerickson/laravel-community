<?php

declare(strict_types=1);

namespace App\Listeners\Integrations;

use App\Actions\Users\SyncProfileAndIntegrationsAction;
use App\Events\UserGroupCreated;
use App\Events\UserGroupDeleted;
use App\Events\UserIntegrationCreated;
use App\Events\UserIntegrationDeleted;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\App;

class SyncProfileAndIntegrations
{
    public function handle(Login|UserGroupCreated|UserGroupDeleted|UserIntegrationCreated|UserIntegrationDeleted $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $user = match ($event::class) {
            Login::class => $event->user,
            UserGroupCreated::class, UserGroupDeleted::class => $event->userGroup->user,
            UserIntegrationCreated::class, UserIntegrationDeleted::class => $event->integration->user,
        };

        if (! $user instanceof User) {
            return;
        }

        SyncProfileAndIntegrationsAction::execute($user);
    }
}
