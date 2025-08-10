<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function store(Request $request, Forum $forum, Topic $topic): RedirectResponse
    {
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
            'created_by' => Auth::id(),
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

    public function update(Request $request, Forum $forum, Topic $topic, Post $post): RedirectResponse
    {
        abort_if(
            boolean: $post->created_by !== Auth::id() && ! $request->user()?->hasRole(Utils::getSuperAdminName()),
            code: 403,
            message: 'You are not authorized to moderate this post.'
        );

        abort_if(
            boolean: $post->topic_id !== $topic->id,
            code: 404,
            message: 'Post not found.'
        );

        $validated = $request->validate([
            'is_published' => 'required|boolean',
        ]);

        $post->update($validated);

        $status = $post->is_published ? 'published' : 'unpublished';

        return to_route('forums.topics.show', compact(['forum', 'topic', 'post']))
            ->with([
                'message' => "Post $status successfully.",
                'messageVariant' => 'success',
            ]);
    }

    public function destroy(Request $request, Forum $forum, Topic $topic, Post $post): RedirectResponse
    {
        abort_if(
            boolean: $post->created_by !== Auth::id() && ! $request->user()?->hasRole(Utils::getSuperAdminName()),
            code: 403,
            message: 'You are not authorized to delete this post.'
        );

        abort_if(
            boolean: $post->topic_id !== $topic->id,
            code: 404,
            message: 'Post not found.'
        );

        $post->delete();

        return to_route('forums.topics.show', compact(['forum', 'topic', 'post']))
            ->with([
                'message' => 'Post deleted successfully.',
                'messageVariant' => 'success',
            ]);
    }
}
