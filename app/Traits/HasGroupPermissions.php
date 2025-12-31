<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Model
 */
trait HasGroupPermissions
{
    public function getGroupPermissions(?User $user = null): array
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return [
                'canRead' => false,
                'canWrite' => false,
                'canDelete' => false,
            ];
        }

        $userGroupIds = $user->groups->pluck('id');

        $resourceGroups = $this->groups()
            ->whereIn('groups.id', $userGroupIds)
            ->get();

        if ($resourceGroups->isEmpty()) {
            return [
                'canRead' => false,
                'canWrite' => false,
                'canDelete' => false,
            ];
        }

        return [
            'canRead' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->read),
            'canWrite' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->write),
            'canDelete' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->delete),
        ];
    }

    public function canUserRead(?User $user = null): bool
    {
        return $this->getGroupPermissions($user)['canRead'];
    }

    public function canUserWrite(?User $user = null): bool
    {
        return $this->getGroupPermissions($user)['canWrite'];
    }

    public function canUserDelete(?User $user = null): bool
    {
        return $this->getGroupPermissions($user)['canDelete'];
    }

    public function scopeReadableByUser($query, ?User $user = null)
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        $userGroupIds = $user->groups->pluck('id');

        return $query->whereHas('groups', function ($q) use ($userGroupIds): void {
            $q->whereIn('groups.id', $userGroupIds)
                ->where('read', true);
        });
    }
}
