<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Topic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('viewAny', ForumCategory::class);

        $categories = ForumCategory::query()
            ->active()
            ->ordered()
            ->with(['forums' => function (HasMany|Forum $query): void {
                $query
                    ->active()
                    ->ordered()
                    ->withCount(['topics', 'posts'])
                    ->with(['latestTopics' => function (HasMany|Topic $subQuery): void {
                        $subQuery
                            ->with(['author', 'lastPost.author'])
                            ->limit(3);
                    }]);
            }])
            ->get()
            ->filter(fn (ForumCategory $category) => Auth::user()->can('view', $category))
            ->map(function (ForumCategory $category) {
                $category->setAttribute('forums', $category->forums->filter(fn (Forum $forum) => Auth::user()->can('view', $forum)));

                return $category;
            });

        return Inertia::render('forums/categories/index', [
            'categories' => $categories,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(ForumCategory $category): Response
    {
        $this->authorize('view', $category);

        $forums = $category
            ->forums()
            ->active()
            ->ordered()
            ->withCount(['topics', 'posts'])
            ->with(['latestTopics' => function ($query): void {
                $query->with(['author', 'lastPost.author'])
                    ->limit(3);
            }])
            ->get()
            ->filter(fn (Forum $forum) => Auth::user()->can('view', $forum));

        return Inertia::render('forums/categories/show', [
            'category' => $category,
            'forums' => $forums,
        ]);
    }
}
