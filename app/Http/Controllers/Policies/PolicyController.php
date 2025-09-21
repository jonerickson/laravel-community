<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Data\PolicyCategoryData;
use App\Data\PolicyData;
use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicyCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class PolicyController extends Controller
{
    use AuthorizesRequests;

    public function show(PolicyCategory $category, Policy $policy): Response
    {
        $this->authorize('view', $policy);

        return Inertia::render('policies/show', [
            'category' => PolicyCategoryData::from($category),
            'policy' => PolicyData::from($policy),
        ]);
    }
}
