<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class ForumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any_forums');
    }
}
