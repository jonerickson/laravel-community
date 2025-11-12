<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Forum;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ForumPolicy
{
    protected static ?Collection $defaultGuestGroups = null;

    public function before(?User $user): ?bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return Gate::forUser($user)->check('view_any_forums');
    }

    public function view(?User $user, Forum $forum): bool
    {
        $groups = $user instanceof User ? $user->groups : static::getDefaultGuestGroups();

        return Gate::forUser($user)->check('view_forums')
            && $forum->is_active
            && ($forum->category === null || Gate::forUser($user)->check('view', $forum->category))
            && ($groups->intersect($forum->groups)->isNotEmpty() ?? false);
    }

    protected static function getDefaultGuestGroups(): ?Collection
    {
        return static::$defaultGuestGroups ?? static::$defaultGuestGroups = Group::defaultGuestGroups()->get();
    }
}
