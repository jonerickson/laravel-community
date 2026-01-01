<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PolicyPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Policy $policy): bool
    {
        return $policy->is_active
            && (blank($policy->category) || Gate::forUser($user)->check('view', $policy->category))
            && (! $policy->effective_at || ! $policy->effective_at->isFuture());
    }
}
