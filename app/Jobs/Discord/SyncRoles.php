<?php

declare(strict_types=1);

namespace App\Jobs\Discord;

use App\Models\User;
use App\Services\DiscordApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SyncRoles implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected User $user,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $discordApiService = app(DiscordApiService::class);

        if (! $discordIntegration = $this->user->integrations()->latest()->firstWhere('provider', 'discord')) {
            return;
        }

        if (! $discordId = $discordIntegration->provider_id) {
            return;
        }

        $expectedRoleIds = $this->getExpectedDiscordRoleIds();
        $currentRoleIds = $discordApiService->getUserRoleIds($discordId);

        $rolesToAdd = $expectedRoleIds->diff($currentRoleIds);
        $rolesToRemove = $currentRoleIds->diff($expectedRoleIds);

        foreach ($rolesToAdd as $roleId) {
            $discordApiService->addRole($discordId, $roleId);
        }

        foreach ($rolesToRemove as $roleId) {
            $discordApiService->removeRole($discordId, $roleId);
        }

        Log::info("Synced Discord roles for user {$this->user->id}. Added: {$rolesToAdd->implode(',')}, Removed: {$rolesToRemove->implode(',')}.");
    }

    protected function getExpectedDiscordRoleIds(): Collection
    {
        return $this->user->groups()
            ->with('discordRoles')
            ->get()
            ->pluck('discordRoles')
            ->flatten()
            ->pluck('discord_role_id')
            ->unique()
            ->values();
    }
}
