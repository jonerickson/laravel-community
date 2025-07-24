<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $perPage = $request->input('per_page', 9);

        $posts = Post::query()->blog()->with(['comments', 'author'])->published()->latest()->paginate(
            perPage: $perPage
        );

        return Inertia::render('blog/index', [
            'posts' => Inertia::merge(fn () => $posts->items()),
            'postsPagination' => Arr::except($posts->toArray(), ['data']),
        ]);
    }
}
