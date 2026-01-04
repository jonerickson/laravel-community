<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Model
 */
trait HasForumPermissions
{
    public function getForumPermissions(?User $user = null): array
    {
        $user ??= Auth::user();

        if ($user instanceof User) {
            $userGroupIds = $user->groups->pluck('id');
        } else {
            $userGroupIds = collect(Group::defaultGuestGroup()?->id);
        }

        $userGroupIds = $userGroupIds
            ->filter()
            ->unique()
            ->values();

        if ($userGroupIds->isEmpty()) {
            return [
                'canCreate' => false,
                'canRead' => false,
                'canUpdate' => false,
                'canDelete' => false,
                'canModerate' => false,
                'canReply' => false,
                'canReport' => false,
                'canPin' => false,
                'canLock' => false,
                'canMove' => false,
            ];
        }

        $resourceGroups = $this->groups
            ->whereIn('id', $userGroupIds)
            ->filter()
            ->unique('id')
            ->values();

        if ($resourceGroups->isEmpty()) {
            return [
                'canCreate' => false,
                'canRead' => false,
                'canUpdate' => false,
                'canDelete' => false,
                'canModerate' => false,
                'canReply' => false,
                'canReport' => false,
                'canPin' => false,
                'canLock' => false,
                'canMove' => false,
            ];
        }

        return [
            'canCreate' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->create),
            'canRead' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->read),
            'canUpdate' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->update),
            'canDelete' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->delete),
            'canModerate' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->moderate),
            'canReply' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->reply),
            'canReport' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->report),
            'canPin' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->pin),
            'canLock' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->lock),
            'canMove' => $resourceGroups->some(fn ($group): bool => (bool) $group->pivot->move),
        ];
    }

    public function canUserRead(?User $user = null): bool
    {
        return $this->getForumPermissions($user)['canRead'];
    }

    public function canUserWrite(?User $user = null): bool
    {
        return $this->getForumPermissions($user)['canCreate'];
    }

    public function canUserDelete(?User $user = null): bool
    {
        return $this->getForumPermissions($user)['canDelete'];
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
