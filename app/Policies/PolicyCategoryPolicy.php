<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PolicyCategory;
use App\Models\User;

class PolicyCategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, PolicyCategory $category): bool
    {
        return $category->is_active;
    }
}
