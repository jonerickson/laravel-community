<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;

class PermissionService
{
    public static function hasPermissionTo(string $permission, ?User $user = null): bool
    {
        if (blank($user) && ($guestGroup = Group::query()->defaultGuestGroups()->first())) {
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

    public static function mapFrontendPermissions(?User $user = null): array
    {
        if (blank($user) && ($guestGroup = Group::query()->defaultGuestGroups()->first())) {
            return $guestGroup->getAllPermissions()->map(fn (Permission $permission) => $permission->name)->toArray();
        }

        if (blank($user)) {
            return [];
        }

        $permissions = $user->getAllPermissions();

        foreach ($user->groups as $group) {
            $permissions->push(...$group->getAllPermissions());
        }

        return $permissions
            ->map(fn (Permission $permission) => $permission->name)
            ->filter()
            ->unique()
            ->toArray();
    }
}
