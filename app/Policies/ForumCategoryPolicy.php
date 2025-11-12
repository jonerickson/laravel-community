<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ForumCategoryPolicy
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
        return Gate::forUser($user)->check('view_any_forums_categories');
    }

    public function view(?User $user, ForumCategory $category): bool
    {
        $groups = $user instanceof User ? $user->groups : static::getDefaultGuestGroups();

        return Gate::forUser($user)->check('view_forums_category')
            && $category->is_active
            && ($groups->intersect($category->groups)->isNotEmpty() ?? false);
    }

    protected static function getDefaultGuestGroups(): ?Collection
    {
        return static::$defaultGuestGroups ?? static::$defaultGuestGroups = Group::defaultGuestGroups()->get();
    }
}
