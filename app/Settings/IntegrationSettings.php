<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\DiscordNameSyncDirection;
use Spatie\LaravelSettings\Settings;

class IntegrationSettings extends Settings
{
    public bool $intercom_enabled = false;

    public ?string $intercom_app_id = null;

    public bool $intercom_auth_required = false;

    public ?string $intercom_secret_key = null;

    public bool $discord_enabled = false;

    public ?string $discord_guild_id = null;

    public ?string $discord_bot_token = null;

    public bool $discord_name_sync_enabled = false;

    public ?DiscordNameSyncDirection $discord_name_sync_direction = null;

    public bool $discord_name_sync_enforced = false;

    public static function group(): string
    {
        return 'integrations';
    }
}
