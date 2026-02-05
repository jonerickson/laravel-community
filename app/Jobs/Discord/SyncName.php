<?php

declare(strict_types=1);

namespace App\Jobs\Discord;

use App\Enums\DiscordNameSyncDirection;
use App\Models\User;
use App\Services\Integrations\DiscordService;
use App\Settings\IntegrationSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class SyncName implements ShouldQueue
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
    public function handle(IntegrationSettings $settings): void
    {
        if (! $settings->discord_name_sync_enabled) {
            return;
        }

        if (! $settings->discord_name_sync_direction instanceof DiscordNameSyncDirection) {
            return;
        }

        $user = User::find($this->userId);

        if (! $user instanceof User) {
            return;
        }

        $discordIntegration = $user->integrations()->latest()->firstWhere('provider', 'discord');

        if (! $discordIntegration) {
            return;
        }

        $discordId = $discordIntegration->provider_id;

        if (! $discordId) {
            return;
        }

        $discordService = app(DiscordService::class);

        if (! $discordService->isUserInServer($discordId)) {
            return;
        }

        match ($settings->discord_name_sync_direction) {
            DiscordNameSyncDirection::DiscordToApp => $this->syncDiscordToApp($user, $discordService, $discordId, $discordIntegration),
            DiscordNameSyncDirection::AppToDiscord => $this->syncAppToDiscord($user, $discordService, $discordId),
        };
    }

    protected function syncDiscordToApp(User $user, DiscordService $discordService, string $discordId, mixed $discordIntegration): void
    {
        $discordName = $discordService->getMemberNickname($discordId);

        if (! $discordName) {
            $discordName = $discordIntegration->provider_name;
        }

        if (! $discordName || $user->name === $discordName) {
            return;
        }

        $oldName = $user->name;
        $user->update(['name' => $discordName]);

        $user->logIntegrationSync(
            provider: 'discord',
            type: 'name',
            details: [
                'direction' => DiscordNameSyncDirection::DiscordToApp->value,
                'old_name' => $oldName,
                'new_name' => $discordName,
            ]
        );
    }

    protected function syncAppToDiscord(User $user, DiscordService $discordService, string $discordId): void
    {
        $currentNickname = $discordService->getMemberNickname($discordId);

        if ($currentNickname === $user->name) {
            return;
        }

        $success = $discordService->setMemberNickname($discordId, $user->name);

        if ($success) {
            $user->logIntegrationSync(
                provider: 'discord',
                type: 'name',
                details: [
                    'direction' => DiscordNameSyncDirection::AppToDiscord->value,
                    'old_name' => $currentNickname,
                    'new_name' => $user->name,
                ]
            );
        }
    }
}
