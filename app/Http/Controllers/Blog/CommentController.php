<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\StoreCommentRequest;
use App\Http\Requests\Blog\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('view', $post);
        $this->authorize('create', Comment::class);

        $post->comments()->create([
            'content' => $request->validated('content'),
            'parent_id' => $request->validated('parent_id'),
        ]);

        return to_route('blog.show', [$post])
            ->with('message', 'Your comment was successfully added.');
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateCommentRequest $request, Post $post, Comment $comment): RedirectResponse
    {
        $this->authorize('view', $post);
        $this->authorize('update', $comment);

        $comment->update($request->only('content'));

        return to_route('blog.show', [$post])
            ->with('message', 'The comment has been successfully updated.');
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Post $post, Comment $comment): RedirectResponse
    {
        $this->authorize('view', $comment);
        $this->authorize('delete', $comment);

        $comment->delete();

        return to_route('blog.show', [$post])
            ->with('message', 'The comment was successfully deleted.');
    }
}
