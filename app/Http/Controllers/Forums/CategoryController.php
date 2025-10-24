<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Data\ForumCategoryData;
use App\Data\ForumData;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Topic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
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
            ->whereNull('parent_id')
            ->active()
            ->ordered()
            ->with('image')
            ->with(['children' => function (HasMany|ForumCategory $query): void {
                $query
                    ->active()
                    ->ordered()
                    ->with('image');
            }])
            ->with(['forums' => function (HasMany|Forum $query): void {
                $query
                    ->active()
                    ->ordered()
                    ->withCount(['topics', 'posts'])
                    ->with(['latestTopics' => function (HasMany|Topic $subQuery): void {
                        $subQuery
                            ->with(['forum.category', 'author', 'lastPost.author'])
                            ->limit(3);
                    }]);
            }])
            ->get()
            ->filter(fn (ForumCategory $category) => Gate::check('view', $category))
            ->map(function (ForumCategory $category): ForumCategory {
                $category->setRelation(
                    'children',
                    $category->children
                        ->filter(fn (ForumCategory $child) => Gate::check('view', $child))
                        ->values()
                );

                $category->setRelation(
                    'forums',
                    $category->forums
                        ->filter(fn (Forum $forum) => Gate::check('view', $forum))
                        ->map(function (Forum $forum): Forum {
                            $forum->setRelation(
                                'latestTopics',
                                $forum->latestTopics->filter(fn (Topic $topic) => Gate::check('view', $topic))->values()
                            );

                            return $forum;
                        })
                        ->values()
                );

                return $category;
            })
            ->values();

        return Inertia::render('forums/categories/index', [
            'categories' => ForumCategoryData::collect($categories),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(ForumCategory $category): Response
    {
        $this->authorize('view', $category);

        $category->load([
            'image',
            'parent',
            'children' => function (HasMany|ForumCategory $query): void {
                $query
                    ->active()
                    ->ordered()
                    ->with('image')
                    ->with(['forums' => function (HasMany|Forum $forumsQuery): void {
                        $forumsQuery
                            ->active()
                            ->ordered()
                            ->withCount(['topics', 'posts'])
                            ->with(['latestTopics' => function (HasMany|Topic $topicsQuery): void {
                                $topicsQuery
                                    ->with(['forum.category', 'author', 'lastPost.author'])
                                    ->limit(3);
                            }]);
                    }]);
            },
        ]);

        $category->setRelation(
            'children',
            $category->children
                ->filter(fn (ForumCategory $child) => Gate::check('view', $child))
                ->values()
        );

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
            ->filter(fn (Forum $forum) => Gate::check('view', $forum))
            ->map(function (Forum $forum): Forum {
                $forum->setRelation(
                    'latestTopics',
                    $forum->latestTopics->filter(fn (Topic $topic) => Gate::check('view', $topic))->values()
                );

                return $forum;
            })
            ->values();

        return Inertia::render('forums/categories/show', [
            'category' => ForumCategoryData::from($category),
            'forums' => ForumData::collect($forums),
        ]);
    }
}
