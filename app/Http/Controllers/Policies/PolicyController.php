<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Data\PolicyCategoryData;
use App\Data\PolicyData;
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

        if ($policy->effective_at && $policy->effective_at->isFuture()) {
            abort(404);
        }

        $policy->loadMissing(['author', 'category']);

        return Inertia::render('policies/show', [
            'category' => PolicyCategoryData::from($category),
            'policy' => PolicyData::from($policy),
        ]);
    }
}
