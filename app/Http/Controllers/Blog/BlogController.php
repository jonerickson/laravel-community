<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Data\CommentData;
use App\Data\PostData;
use App\Data\RecentViewerData;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class BlogController extends Controller
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
        $this->authorize('viewAny', Post::class);

        $posts = PostData::collect(collect($this->cache->getByKey('blog.index'))
            ->filter(fn (array $post) => Gate::getPolicyFor(Post::class)->view($this->user, PostData::from($post)))
            ->values()
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('blog/index', [
            'posts' => Inertia::scroll(fn () => $posts->items()),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Post $post): Response
    {
        $this->authorize('view', $post);

        $post->incrementViews();
        $post->loadMissing(['author']);
        $post->loadCount(['views', 'comments']);

        $comments = CommentData::collect($post
            ->comments()
            ->with(['author.groups', 'replies.author.groups', 'parent', 'likes.author'])
            ->latest()
            ->get()
            ->filter(fn (Comment $comment) => Gate::check('view', $comment))
            ->values()
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('blog/show', [
            'post' => PostData::from($post),
            'comments' => Inertia::scroll(fn () => $comments->items(), 'comments'),
            'recentViewers' => Inertia::defer(fn (): array => RecentViewerData::collect($post->getRecentViewers()), 'recentViewers'),
        ]);
    }
}
