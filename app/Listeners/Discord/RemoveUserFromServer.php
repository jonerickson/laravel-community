<?php

declare(strict_types=1);

namespace App\Listeners\Discord;

use App\Events\UserIntegrationDeleted;
use App\Services\Integrations\DiscordService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class RemoveUserFromServer implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function __construct(
        private readonly DiscordService $discord,
    ) {
        //
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(UserIntegrationDeleted $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if ($event->integration->provider !== 'discord') {
            return;
        }

        if (blank($discordId = $event->integration->provider_id)) {
            return;
        }

        if (! $this->discord->isUserInServer($discordId)) {
            return;
        }

        $this->discord->removeUserFromServer(
            discordUserId: $discordId,
        );
    }
}
