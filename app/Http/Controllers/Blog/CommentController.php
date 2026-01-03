<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\StoreCommentRequest;
use App\Http\Requests\Blog\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('view', $post);
        $this->authorize('create', Comment::class);

        $post->comments()->create([
            'content' => $request->validated('content'),
            'parent_id' => $request->validated('parent_id'),
        ]);

        return back()->with('message', 'Your comment was successfully added.');
    }

    public function update(UpdateCommentRequest $request, Post $post, Comment $comment): RedirectResponse
    {
        $this->authorize('view', $post);
        $this->authorize('update', $comment);

        $comment->update($request->only('content'));

        return back()->with('message', 'The comment has been successfully updated.');
    }

    public function destroy(Post $post, Comment $comment): RedirectResponse
    {
        $this->authorize('view', $comment);
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('message', 'The comment was successfully deleted.');
    }
}
