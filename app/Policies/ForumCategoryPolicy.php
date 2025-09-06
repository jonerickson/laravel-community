<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Forum;
use App\Models\User;

class ForumCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_forums_categories');
    }

    public function view(User $user, Forum $forum): bool
    {
        return $user->hasPermissionTo('view_forums_category');
    }
}
