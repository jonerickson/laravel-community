<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        abort_if(! $post->is_published, 404);

        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|int',
        ]);

        $post->comments()->create([
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_approved' => true,
        ]);

        return to_route('blog.show', [$post]);
    }
}
