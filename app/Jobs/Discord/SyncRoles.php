<?php

declare(strict_types=1);

namespace App\Jobs\Discord;

use App\Models\Group;
use App\Models\User;
use App\Services\Integrations\DiscordService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

class SyncRoles implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $userId,
    ) {
        //
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $user = User::findOrFail($this->userId);

        $discordApiService = app(DiscordService::class);

        if (! $discordIntegration = $user->integrations()->latest()->firstWhere('provider', 'discord')) {
            return;
        }

        if (! $discordId = $discordIntegration->provider_id) {
            return;
        }

        if (! $discordApiService->isUserInServer($discordId)) {
            if (! $accessToken = $discordIntegration->access_token) {
                return;
            }

            $discordApiService->addUserToServer(
                discordUserId: $discordId,
                accessToken: $accessToken,
            );
        }

        /** @var Collection $expectedRoleIds */
        $expectedRoleIds = $user->getExpectedDiscordRoleIds();

        $platformRoleIds = Group::query()
            ->with('discordRoles')
            ->whereHas('discordRoles')
            ->get()
            ->pluck('discordRoles')
            ->flatten()
            ->pluck('discord_role_id')
            ->unique()
            ->values();

        $currentRoleIds = $discordApiService->getUserRoleIds($discordId);

        $rolesToAdd = $expectedRoleIds->diff($currentRoleIds);
        $rolesToRemove = $currentRoleIds
            ->intersect($platformRoleIds)
            ->diff($expectedRoleIds);

        foreach ($rolesToAdd as $roleId) {
            $discordApiService->addRole($discordId, $roleId);
        }

        foreach ($rolesToRemove as $roleId) {
            $discordApiService->removeRole($discordId, $roleId);
        }

        $discordIntegration->update([
            'last_synced_at' => now(),
        ]);

        $user->logIntegrationSync(
            provider: 'discord',
            type: 'roles',
            details: [
                'roles_added' => $rolesToAdd,
                'roles_removed' => $rolesToRemove,
            ]
        );
    }
}
