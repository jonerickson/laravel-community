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
        $this->authorize('update', $post);
        $this->authorize('create', Comment::class);

        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|int',
        ]);

        $post->comments()->create([
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_approved' => true,
        ]);

        return to_route('blog.show', [$post])
            ->with('message', 'Your comment was successfully added.');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Request $request, Post $post, Comment $comment): RedirectResponse
    {
        $this->authorize('update', $post);
        $this->authorize('update', $comment);

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
        $this->authorize('update', $post);
        $this->authorize('delete', $comment);

        $comment->delete();

        return to_route('blog.show', [$post])
            ->with('message', 'The comment was successfully deleted.');
    }
}
