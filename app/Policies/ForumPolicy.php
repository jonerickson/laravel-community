<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ForumPolicy
{
    public function before(User $user): ?bool
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
        return Gate::forUser($user)->check('view_forums')
            && $forum->is_active
            && $forum->category->is_active;
    }
}
