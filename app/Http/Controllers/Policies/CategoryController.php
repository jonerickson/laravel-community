<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

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
            'categories' => $categories,
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
            'category' => $category,
            'policies' => $policies,
        ]);
    }
}
