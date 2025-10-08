<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Data\PolicyCategoryData;
use App\Data\PolicyData;
use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicyCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', PolicyCategory::class);

        $categories = PolicyCategory::active()
            ->ordered()
            ->with(['activePolicies' => function (HasMany $query): void {
                $query->effective()->ordered();
            }])
            ->get()
            ->filter(fn (PolicyCategory $category) => Gate::check('view', $category))
            ->values();

        return Inertia::render('policies/categories/index', [
            'categories' => PolicyCategoryData::collect($categories),
        ]);
    }

    public function show(PolicyCategory $category): Response
    {
        $this->authorize('view', $category);

        $policies = $category
            ->activePolicies()
            ->effective()
            ->ordered()
            ->get()
            ->filter(fn (Policy $policy) => Gate::check('view', $policy))
            ->values();

        return Inertia::render('policies/categories/show', [
            'category' => PolicyCategoryData::from($category),
            'policies' => PolicyData::collect($policies),
        ]);
    }
}
