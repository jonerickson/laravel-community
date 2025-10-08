<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Data\CommentData;
use App\Data\PostData;
use App\Data\RecentViewerData;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
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
            'posts' => Inertia::scroll(fn () => $posts->items()),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Request $request, Post $post): Response
    {
        $this->authorize('view', $post);

        $post->incrementViews();

        $comments = CommentData::collect($post->comments()
            ->with(['author', 'replies', 'replies.author', 'parent'])
            ->latest()
            ->get()
            ->filter(fn (Comment $comment) => Gate::check('view', [$comment, $post]))
            ->values()
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('blog/show', [
            'post' => PostData::from($post),
            'comments' => Inertia::scroll(fn () => $comments->items()),
            'recentViewers' => Inertia::defer(fn (): array => RecentViewerData::collect($post->getRecentViewers())),
        ]);
    }
}
