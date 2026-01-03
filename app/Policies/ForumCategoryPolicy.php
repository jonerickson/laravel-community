<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WarningConsequenceType;
use App\Models\ForumCategory;
use App\Models\User;

class ForumCategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ForumCategory $category): bool
    {
        return $category->is_active
            && (data_get($category->getForumPermissions($user), 'canRead') ?? false);
    }

    public function create(?User $user, ?ForumCategory $category = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return (blank($category) || $this->view($user, $category))
            && (blank($category) || (data_get($category->getForumPermissions($user), 'canCreate') ?? false));
    }

    public function update(?User $user, ForumCategory $category): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $category)
            && (data_get($category->getForumPermissions($user), 'canUpdate') ?? false);
    }

    public function delete(?User $user, ForumCategory $category): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $category)
            && (data_get($category->getForumPermissions($user), 'canDelete') ?? false);
    }

    public function moderate(?User $user, ForumCategory $category): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $category)
            && (data_get($category->getForumPermissions($user), 'canModerate') ?? false);
    }

    public function reply(?User $user, ForumCategory $category): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->active_consequence?->type === WarningConsequenceType::PostRestriction || $user->active_consequence?->type === WarningConsequenceType::Ban) {
            return false;
        }

        return $this->view($user, $category)
            && (data_get($category->getForumPermissions($user), 'canReply') ?? false);
    }

    public function report(?User $user, ForumCategory $category): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $category)
            && (data_get($category->getForumPermissions($user), 'canReport') ?? false);
    }

    public function pin(?User $user, ForumCategory $category): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $category)
            && (data_get($category->getForumPermissions($user), 'canPin') ?? false);
    }

    public function lock(?User $user, ForumCategory $category): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $category)
            && (data_get($category->getForumPermissions($user), 'canLock') ?? false);
    }

    public function move(?User $user, ForumCategory $category): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $this->view($user, $category)
            && (data_get($category->getForumPermissions($user), 'canMove') ?? false);
    }
}
