<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscordNameSyncDirection: string implements HasLabel
{
    case DiscordToApp = 'discord_to_app';
    case AppToDiscord = 'app_to_discord';

    public function getLabel(): string
    {
        return match ($this) {
            self::DiscordToApp => 'Discord to app',
            self::AppToDiscord => 'App to Discord',
        };
    }
}
