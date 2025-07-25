<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicyCategory;
use Inertia\Inertia;
use Inertia\Response;

class PolicyController extends Controller
{
    public function show(PolicyCategory $category, Policy $policy): Response
    {
        abort_if(! $policy->is_active, 404);
        abort_if(! $category->is_active, 404);
        abort_if($policy->policy_category_id !== $category->id, 404);

        if ($policy->effective_date && $policy->effective_date->isFuture()) {
            abort(404);
        }

        return Inertia::render('policies/show', [
            'category' => $category,
            'policy' => $policy,
        ]);
    }
}
