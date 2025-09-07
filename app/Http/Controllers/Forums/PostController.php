<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function store(Request $request, Forum $forum, Topic $topic): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('create', Post::class);

        $validated = $request->validate([
            'content' => 'required|string',
        ], [
            'content.required' => 'Please provide a reply before posting.',
        ]);

        $topic->posts()->create([
            'type' => PostType::Forum,
            'title' => 'Re: '.$topic->title,
            'content' => $validated['content'],
            'is_published' => true,
            'published_at' => now(),
        ]);

        $totalPosts = $topic->posts()->count();
        $postsPerPage = 10;
        $lastPage = (int) ceil($totalPosts / $postsPerPage);

        return to_route('forums.topics.show', [
            'forum' => $forum,
            'topic' => $topic,
            'page' => $lastPage,
        ])->with('scrollToBottom', true);
    }

    /**
     * @throws AuthorizationException
     */
    public function edit(Request $request, Forum $forum, Topic $topic, Post $post): Response
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('update', $post);

        abort_if(
            boolean: $post->topic_id !== $topic->id,
            code: 404,
            message: 'Post not found.'
        );

        $forum->loadMissing('category');
        $post->loadMissing('author');

        return Inertia::render('forums/posts/edit', [
            'forum' => $forum,
            'topic' => $topic,
            'post' => $post,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Request $request, Forum $forum, Topic $topic, Post $post): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('update', $post);

        abort_if(
            boolean: $post->topic_id !== $topic->id,
            code: 404,
            message: 'Post not found.'
        );

        $validated = $request->validate([
            'content' => 'required|string',
        ], [
            'content.required' => 'Post content cannot be empty.',
        ]);

        $post->update($validated);

        return to_route('forums.topics.show', ['forum' => $forum, 'topic' => $topic])
            ->with([
                'message' => 'Post updated successfully.',
                'messageVariant' => 'success',
            ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Forum $forum, Topic $topic, Post $post): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);
        $this->authorize('delete', $post);

        abort_if(
            boolean: $post->topic_id !== $topic->id,
            code: 404,
            message: 'Post not found.'
        );

        $post->delete();

        return to_route('forums.topics.show', ['forum' => $forum, 'topic' => $topic, 'post' => $post])
            ->with([
                'message' => 'Post deleted successfully.',
                'messageVariant' => 'success',
            ]);
    }
}
