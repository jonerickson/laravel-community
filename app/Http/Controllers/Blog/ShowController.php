<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends Controller
{
    public function __invoke(Post $post): Response
    {
        return Inertia::render('blog/show', [
            'post' => $post->loadMissing(['comments', 'author']),
        ]);
    }
}
