<?php

declare(strict_types=1);

namespace App\Policies;

use App\Data\ForumCategoryData;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ForumCategoryPolicy
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
        return Gate::forUser($user)->check('view_any_forums_categories');
    }

    public function view(?User $user, ForumCategoryData|ForumCategory $category): bool
    {
        $groups = $user instanceof User ? $user->groups : collect([Group::defaultGuestGroup()]);

        if ($category instanceof ForumCategoryData) {
            return Gate::forUser($user)->check('view_forums_category')
                && $category->isActive
                && ($groups->pluck('id')->intersect(collect($category->groups)->pluck('id'))->isNotEmpty() ?? false);
        }

        return Gate::forUser($user)->check('view_forums_category')
            && $category->is_active
            && ($groups->pluck('id')->intersect($category->groups->pluck('id'))->isNotEmpty() ?? false);
    }
}
