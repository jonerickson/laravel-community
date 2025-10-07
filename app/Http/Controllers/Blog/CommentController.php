<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('create', [Comment::class, $post]);

        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|int',
        ]);

        $post->comments()->create([
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return to_route('blog.show', [$post])
            ->with('message', 'Your comment was successfully added.');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Request $request, Post $post, Comment $comment): RedirectResponse
    {
        $this->authorize('update', [$comment, $post]);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update($validated);

        return to_route('blog.show', [$post])
            ->with('message', 'The comment has been successfully updated.');
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Post $post, Comment $comment): RedirectResponse
    {
        $this->authorize('delete', [$comment, $post]);

        $comment->delete();

        return to_route('blog.show', [$post])
            ->with('message', 'The comment was successfully deleted.');
    }
}
