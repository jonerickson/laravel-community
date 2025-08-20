<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        abort_if(
            boolean: ! $post->is_published,
            code: 404,
            message: 'Post not found.'
        );

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

    public function update(Request $request, Post $post, Comment $comment): RedirectResponse
    {
        abort_if(
            boolean: ! $post->is_published,
            code: 404,
            message: 'Post not found.'
        );

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update($validated);

        return to_route('blog.show', [$post]);
    }

    public function destroy(Post $post, Comment $comment): RedirectResponse
    {
        abort_if(
            boolean: ! $post->is_published,
            code: 404,
            message: 'Post not found.'
        );

        $comment->delete();

        return to_route('blog.show', [$post]);
    }
}
