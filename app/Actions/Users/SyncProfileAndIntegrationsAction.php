<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Action;
use App\Jobs\Discord\SyncRoles;
use App\Models\User;
use Throwable;

class SyncProfileAndIntegrationsAction extends Action
{
    public function __construct(
        protected User $user,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): User
    {
        $this->user->syncGroups();

        $this->syncDiscord();
        $this->syncRoblox();

        return $this->user;
    }

    /**
     * @throws Throwable
     */
    protected function syncDiscord(): void
    {
        $integration = $this->user->integrations()->latest()->firstWhere('provider', 'discord');

        if ($integration) {
            RefreshUserIntegrationAction::execute($integration);
        }

        if (config('services.discord.enabled') && config('services.discord.guild_id')) {
            SyncRoles::dispatch($this->user->getKey());
        }
    }

    /**
     * @throws Throwable
     */
    protected function syncRoblox(): void
    {
        $integration = $this->user->integrations()->latest()->firstWhere('provider', 'roblox');

        if ($integration) {
            RefreshUserIntegrationAction::execute($integration);
        }
    }
}
