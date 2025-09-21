<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PolicyCategory;
use App\Models\User;

class PolicyCategoryPolicy
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
        return true;
    }

    public function view(?User $user, PolicyCategory $category): bool
    {
        return $category->is_active;
    }
}
