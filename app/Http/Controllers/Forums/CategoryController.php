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
            ->active()
            ->ordered()
            ->with(['forums' => function (HasMany|Forum $query): void {
                $query
                    ->with(['topics.posts', 'posts', 'follows', 'followers'])
                    ->whereNull('parent_id')
                    ->active()
                    ->ordered()
                    ->with(['latestTopics' => function (HasMany|Topic $subQuery): void {
                        $subQuery
                            ->with(['forum.category', 'author', 'lastPost.author', 'posts.comments.reports'])
                            ->limit(3);
                    }]);
            }])
            ->with(['groups'])
            ->get()
            ->filter(fn (ForumCategory $category) => Gate::check('view', $category))
            ->map(function (ForumCategory $category): ForumCategory {
                $category->setRelation(
                    'forums',
                    $category->forums
                        ->filter(fn (Forum $forum) => Gate::check('view', $forum))
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

        $forums = $category
            ->loadMissing(['forums' => fn (HasMany|Forum $query) => $query->whereNull('parent_id')->active()->ordered()])
            ->loadMissing(['groups', 'forums.latestTopics.posts.reads', 'forums.latestTopics.posts.views', 'forums.latestTopics.posts.likes', 'forums.latestTopics.posts.comments', 'forums.latestTopics.author', 'forums.latestTopics.lastPost.author'])
            ->forums
            ->filter(fn (Forum $forum) => Gate::check('view', $forum))
            ->values();

        return Inertia::render('forums/categories/show', [
            'category' => ForumCategoryData::from($category),
            'forums' => ForumData::collect($forums),
        ]);
    }
}
