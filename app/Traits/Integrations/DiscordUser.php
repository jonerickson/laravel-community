<?php

declare(strict_types=1);

namespace App\Traits\Integrations;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * @mixin User
 */
trait DiscordUser
{
    public function getExpectedDiscordRoleIds(): Collection
    {
        return $this->groups()
            ->with('discordRoles')
            ->get()
            ->pluck('discordRoles')
            ->flatten()
            ->pluck('discord_role_id')
            ->unique()
            ->values();
    }
}
