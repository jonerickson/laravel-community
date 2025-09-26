<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Data\CommentData;
use App\Data\PaginatedData;
use App\Data\PostData;
use App\Data\RecentViewerData;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class BlogController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Post::class);

        $posts = PostData::collect(Post::query()
            ->blog()
            ->with('comments')
            ->with('author')
            ->with('reads')
            ->published()
            ->latest()
            ->get()
            ->filter(fn (Post $post) => Gate::check('view', $post))
            ->values()
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('blog/index', [
            'posts' => Inertia::merge(fn () => $posts->items()->items()),
            'postsPagination' => PaginatedData::from(Arr::except($posts->items()->toArray(), ['data'])),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Request $request, Post $post): Response
    {
        $this->authorize('view', $post);

        $post->incrementViews();

        $comments = CommentData::collect($post->approvedComments()
            ->with(['author', 'replies', 'replies.author', 'parent'])
            ->latest()
            ->get()
            ->filter(fn (Comment $comment) => Gate::check('view', [$comment, $post]))
            ->values()
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('blog/show', [
            'post' => PostData::from($post),
            'comments' => Inertia::defer(fn () => $comments->items()->items()),
            'commentsPagination' => PaginatedData::from(Arr::except($comments->items()->toArray(), ['data'])),
            'recentViewers' => Inertia::defer(fn (): array => RecentViewerData::collect($post->getRecentViewers())),
        ]);
    }
}
