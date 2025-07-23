<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('blog/index', [
            'posts' => Post::query()->with(['comments', 'author'])->published()->latest()->get(),
        ]);
    }
}
