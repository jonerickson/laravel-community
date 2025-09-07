<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Forum;
use App\Models\User;
use App\Services\PermissionService;

class ForumPolicy
{
    public function viewAny(?User $user): bool
    {
        return PermissionService::hasPermissionTo('view_any_forums', $user);
    }

    public function view(?User $user, Forum $forum): bool
    {
        return PermissionService::hasPermissionTo('view_forums', $user);
    }
}
