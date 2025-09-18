<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Post::class);

        /** @var LengthAwarePaginator $posts */
        $posts = Post::query()
            ->blog()
            ->with('comments')
            ->with('author')
            ->with('reads')
            ->published()
            ->latest()
            ->paginate(
                perPage: $request->input('per_page', 9)
            );

        $posts->setCollection(
            collection: $posts->getCollection()->filter(fn (Post $post) => Gate::check('view', $post))
        );

        return Inertia::render('blog/index', [
            'posts' => Inertia::merge(fn () => $posts->items()),
            'postsPagination' => Arr::except($posts->toArray(), ['data']),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Request $request, Post $post): Response
    {
        $this->authorize('view', $post);

        $post->incrementViews();

        $perPage = $request->input('per_page', 10);

        $comments = $post->approvedComments()->with(['author', 'replies', 'replies.author', 'parent'])->latest()->paginate(
            perPage: $perPage
        );

        return Inertia::render('blog/show', [
            'post' => $post->loadMissing(['author']),
            'comments' => Inertia::defer(fn () => $comments->items()),
            'commentsPagination' => Arr::except($comments->toArray(), ['data']),
            'recentViewers' => Inertia::defer(fn (): array => $post->getRecentViewers()),
        ]);
    }
}
