<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Policy;
use App\Models\User;

class PolicyPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Policy $policy): bool
    {
        return $policy->is_active
            && $policy->category->is_active
            && (! $policy->effective_at || ! $policy->effective_at->isFuture());
    }
}
