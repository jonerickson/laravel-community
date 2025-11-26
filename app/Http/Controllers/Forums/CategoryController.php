<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Data\ForumCategoryData;
use App\Data\ForumData;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected readonly CacheService $cache,
        #[CurrentUser]
        protected readonly ?User $user = null,
    ) {
        //
    }

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('viewAny', ForumCategory::class);

        $categories = collect($this->cache->getByKey('forums.categories.index'))
            ->filter(fn (array $category) => Gate::getPolicyFor(ForumCategory::class)->view($this->user, ForumCategoryData::from($category)))
            ->map(function (array $category): array {
                $category['forums'] = collect($category['forums'] ?? [])
                    ->filter(fn (array $forum) => Gate::getPolicyFor(Forum::class)->view($this->user, ForumData::from($forum)))
                    ->values()
                    ->toArray();

                return $category;
            })
            ->values();

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
            ->with(['latestTopic.author', 'latestTopic.lastPost'])
            ->whereNull('parent_id')
            ->active()
            ->ordered()
            ->withCount(['topics', 'posts'])
            ->get()
            ->filter(fn (Forum $forum) => Gate::check('view', $forum))
            ->values();

        return Inertia::render('forums/categories/show', [
            'category' => ForumCategoryData::from($category),
            'forums' => Inertia::defer(fn (): Collection => ForumData::collect($forums)),
        ]);
    }
}
