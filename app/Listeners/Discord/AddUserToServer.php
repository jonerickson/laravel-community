<?php

declare(strict_types=1);

namespace App\Listeners\Discord;

use App\Events\UserIntegrationCreated;
use App\Services\Integrations\DiscordService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class AddUserToServer implements ShouldQueue
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
    public function handle(UserIntegrationCreated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if ($event->integration->provider !== 'discord') {
            return;
        }

        if (blank($discordId = $event->integration->provider_id) || blank($accessToken = $event->integration->access_token)) {
            return;
        }

        if ($this->discord->isUserInServer($discordId)) {
            return;
        }

        /** @var Collection $roleIds */
        $roleIds = $event->integration->user->getExpectedDiscordRoleIds();

        $this->discord->addUserToServer(
            discordUserId: $discordId,
            accessToken: $accessToken,
            roleIds: $roleIds->toArray(),
        );
    }
}
