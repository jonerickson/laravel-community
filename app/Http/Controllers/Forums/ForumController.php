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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ForumController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Forum::class);

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

        return Inertia::render('forums/index', [
            'categories' => $categories,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Forum $forum): Response
    {
        $this->authorize('view', $forum);

        /** @var LengthAwarePaginator $topics */
        $topics = $forum->topics()
            ->with(['author', 'lastPost.author'])
            ->latestActivity()
            ->paginate(20);

        $topics->setCollection(
            collection: $topics->getCollection()->filter(fn (Topic $topic) => Auth::user()->can('view', $topic))
        );

        return Inertia::render('forums/show', [
            'forum' => $forum,
            'topics' => Inertia::merge(fn () => $topics->items()),
            'topicsPagination' => Arr::except($topics->toArray(), ['data']),
        ]);
    }
}
