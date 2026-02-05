<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('integrations.discord_enabled', false);
        $this->migrator->add('integrations.discord_guild_id', null);
        $this->migrator->add('integrations.discord_bot_token', null);
        $this->migrator->add('integrations.discord_name_sync_enabled', false);
        $this->migrator->add('integrations.discord_name_sync_direction', null);
        $this->migrator->add('integrations.discord_name_sync_enforced', false);
    }
};
