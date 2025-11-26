<?php

declare(strict_types=1);

namespace App\Policies;

use App\Data\ForumData;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ForumPolicy
{
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

    public function view(?User $user, ForumData|Forum $forum): bool
    {
        $groups = $user instanceof User ? $user->groups : collect([Group::defaultGuestGroup()]);

        if ($forum instanceof ForumData) {
            return Gate::forUser($user)->check('view_forums')
                && $forum->isActive
                && (blank($forum->category) || Gate::getPolicyFor(ForumCategory::class)->view($user, $forum->category))
                && ($groups->pluck('id')->intersect(collect($forum->groups)->pluck('id'))->isNotEmpty() ?? false);
        }

        return Gate::forUser($user)->check('view_forums')
            && $forum->is_active
            && (blank($forum->category) || Gate::forUser($user)->check('view', $forum->category))
            && ($groups->pluck('id')->intersect($forum->groups->pluck('id'))->isNotEmpty() ?? false);
    }
}
