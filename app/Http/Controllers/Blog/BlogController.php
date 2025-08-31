<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 9);

        $posts = Post::query()->blog()->with(['comments', 'author', 'reads'])->published()->latest()->paginate(
            perPage: $perPage
        );

        return Inertia::render('blog/index', [
            'posts' => Inertia::merge(fn () => $posts->items()),
            'postsPagination' => Arr::except($posts->toArray(), ['data']),
        ]);
    }

    public function show(Request $request, Post $post): Response
    {
        abort_if(
            boolean: ! $post->is_published,
            code: 404,
            message: 'Post not found.'
        );

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
