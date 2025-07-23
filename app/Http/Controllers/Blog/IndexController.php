<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $perPage = $request->input('per_page', 9);

        $posts = Post::query()->with(['comments', 'author'])->published()->latest()->paginate(
            perPage: $perPage
        );

        return Inertia::render('blog/index', [
            'posts' => Inertia::merge(fn () => $posts->items()),
            'postsPagination' => $posts->toArray(),
        ]);
    }
}
