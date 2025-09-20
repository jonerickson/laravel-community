<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ForumCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ForumCategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return Gate::forUser($user)->check('view_any_forums_categories');
    }

    public function view(?User $user, ForumCategory $category): bool
    {
        return Gate::forUser($user)->check('view_forums_category')
            && $category->is_active;
    }
}
