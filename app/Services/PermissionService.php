<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Group;
use App\Models\User;

class PermissionService
{
    protected static ?Group $defaultGuestGroup = null;

    public static function hasPermissionTo(string $permission, ?User $user = null): bool
    {
        if (blank($user) && ($guestGroup = static::getDefaultGuestGroup())) {
            return $guestGroup->hasPermissionTo($permission);
        }

        if (blank($user)) {
            return false;
        }

        if ($user->hasPermissionTo($permission)) {
            return true;
        }

        foreach ($user->groups as $group) {
            if ($group->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    protected static function getDefaultGuestGroup(): ?Group
    {
        return static::$defaultGuestGroup ?? static::$defaultGuestGroup = Group::query()->defaultGuestGroups()->first();
    }
}
