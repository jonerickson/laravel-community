<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Data\ForumCategoryData;
use App\Data\ForumData;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', ForumCategory::class);

        $categories = ForumCategory::query()
            ->active()
            ->ordered()
            ->with(['groups'])
            ->with(['forums' => function (HasMany|Forum $query): void {
                $query
                    ->whereNull('parent_id')
                    ->withCount(['topics', 'posts'])
                    ->active()
                    ->ordered()
                    ->with(['groups'])
                    ->with(['latestTopics' => function (HasMany|Topic $subQuery): void {
                        $subQuery
                            ->withCount('posts')
                            ->with(['author.groups', 'lastPost.pendingReports', 'reads', 'views', 'posts.likes', 'posts.pendingReports', 'follows', 'forum.groups', 'forum.category', 'forum.follows'])
                            ->limit(3);
                    }]);
            }])
            ->get()
            ->loadCount([
                'forums as posts_count' => function (Builder $query): void {
                    $query
                        ->join('topics', 'topics.forum_id', '=', 'forums.id')
                        ->join('posts', 'posts.topic_id', '=', 'topics.id');
                },
            ])
            ->filter(fn (ForumCategory $category) => Gate::check('view', $category))
            ->map(function (ForumCategory $category): ForumCategory {
                $category->setRelation('forums', $category->forums
                    ->filter(fn (Forum $forum) => Gate::check('view', $forum))
                    ->values());

                return $category;
            })
            ->values();

        return Inertia::render('forums/categories/index', [
            'categories' => ForumCategoryData::collect($categories),
        ]);
    }

    public function show(ForumCategory $category): Response
    {
        $this->authorize('view', $category);

        return Inertia::render('forums/categories/show', [
            'category' => ForumCategoryData::from($category),
            'forums' => Inertia::defer(fn (): Collection => ForumData::collect($category
                ->forums()
                ->with(['latestTopic.author', 'latestTopic.lastPost'])
                ->whereNull('parent_id')
                ->active()
                ->ordered()
                ->withCount(['topics', 'posts'])
                ->get()
                ->filter(fn (Forum $forum) => Gate::check('view', $forum))
                ->values())),
        ]);
    }
}
