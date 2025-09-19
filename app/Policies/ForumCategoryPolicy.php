<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ForumCategory;
use App\Models\User;
use App\Services\PermissionService;

class ForumCategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return PermissionService::hasPermissionTo('view_any_forums_categories', $user);
    }

    public function view(?User $user, ForumCategory $category): bool
    {
        return PermissionService::hasPermissionTo('view_forums_category', $user)
            && $category->is_active;
    }
}
