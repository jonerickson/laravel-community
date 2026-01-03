<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WarningConsequenceType;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ForumPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Forum $forum): bool
    {
        return $forum->is_active
            && (blank($forum->category) || Gate::forUser($user)->check('view', $forum->category))
            && (data_get($forum->getForumPermissions($user), 'canRead') ?? false)
            && (blank($forum->category) || data_get($forum->category->getForumPermissions($user), 'canRead') ?? false);
    }

    public function create(?User $user, ?Forum $forum = null, ?ForumCategory $category = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return (blank($forum) || $this->view($user, $forum))
            && (blank($forum) || (data_get($forum->getForumPermissions($user), 'canCreate') ?? false))
            && (blank($category) || Gate::forUser($user)->check('view', $category))
            && (blank($category) || (data_get($category->getForumPermissions($user), 'canCreate') ?? false));
    }

    public function update(?User $user, Forum $forum): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $forum)
            && (data_get($forum->getForumPermissions($user), 'canUpdate') ?? false)
            && (blank($forum->category) || (data_get($forum->category->getForumPermissions($user), 'canUpdate') ?? false));
    }

    public function delete(?User $user, Forum $forum): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $forum)
            && (data_get($forum->getForumPermissions($user), 'canDelete') ?? false)
            && (is_null($forum->category) || (data_get($forum->category->getForumPermissions($user), 'canDelete') ?? false));
    }

    public function moderate(?User $user, Forum $forum): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $forum)
            && (blank($forum) || data_get($forum->getForumPermissions($user), 'canModerate') ?? false)
            && (blank($forum->category) || data_get($forum->category->getForumPermissions($user), 'canModerate') ?? false);
    }

    public function reply(?User $user, Forum $forum): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->active_consequence?->type === WarningConsequenceType::PostRestriction || $user->active_consequence?->type === WarningConsequenceType::Ban) {
            return false;
        }

        return $this->view($user, $forum)
            && (blank($forum) || data_get($forum->getForumPermissions($user), 'canReply') ?? false)
            && (blank($forum->category) || data_get($forum->category->getForumPermissions($user), 'canReply') ?? false);
    }

    public function report(?User $user, Forum $forum): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $forum)
            && (blank($forum) || data_get($forum->getForumPermissions($user), 'canReport') ?? false)
            && (blank($forum?->category) || data_get($forum->category->getForumPermissions($user), 'canReport') ?? false);
    }

    public function pin(?User $user, Forum $forum): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $forum)
            && (blank($forum) || data_get($forum->getForumPermissions($user), 'canPin') ?? false)
            && (blank($forum?->category) || data_get($forum->category->getForumPermissions($user), 'canPin') ?? false);
    }

    public function lock(?User $user, Forum $forum): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $forum)
            && (blank($forum) || data_get($forum->getForumPermissions($user), 'canLock') ?? false)
            && (blank($forum?->category) || data_get($forum->category->getForumPermissions($user), 'canLock') ?? false);
    }

    public function move(?User $user, Forum $forum): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $forum)
            && (blank($forum) || data_get($forum->getForumPermissions($user), 'canMove') ?? false)
            && (blank($forum?->category) || data_get($forum->category->getForumPermissions($user), 'canMove') ?? false);
    }
}
