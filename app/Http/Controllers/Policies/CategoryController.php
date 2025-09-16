<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Data\PolicyCategoryData;
use App\Data\PolicyData;
use App\Http\Controllers\Controller;
use App\Models\PolicyCategory;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = PolicyCategory::active()
            ->ordered()
            ->with(['activePolicies' => function ($query): void {
                $query->effective()->ordered();
            }])
            ->get();

        return Inertia::render('policies/index', [
            'categories' => PolicyCategoryData::collect($categories),
        ]);
    }

    public function show(PolicyCategory $category): Response
    {
        abort_if(! $category->is_active, 404);

        $policies = $category
            ->activePolicies()
            ->effective()
            ->ordered()
            ->get();

        return Inertia::render('policies/category', [
            'category' => PolicyCategoryData::from($category),
            'policies' => PolicyData::collect($policies),
        ]);
    }
}
