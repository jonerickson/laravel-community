<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Data\CommentData;
use App\Data\PostData;
use App\Data\RecentViewerData;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class BlogController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', Post::class);

        $posts = PostData::collect(Post::query()
            ->blog()
            ->with(['comments.likes', 'author.groups', 'reads', 'likes', 'pendingReports'])
            ->withCount(['views', 'comments'])
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

    public function show(Post $post): Response
    {
        $this->authorize('view', $post);

        $post->incrementViews();
        $post->loadMissing(['author.groups']);
        $post->loadCount(['views', 'comments']);

        $comments = CommentData::collect($post
            ->comments()
            ->with(['parent', 'likes.author'])
            ->with(['replies.author.groups' => function (BelongsToMany|Group $query): void {
                $query->active()->visible()->ordered();
            }])
            ->with(['author.groups' => function (BelongsToMany|Group $query): void {
                $query->active()->visible()->ordered();
            }])
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
