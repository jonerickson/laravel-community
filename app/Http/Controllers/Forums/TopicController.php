<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Topic;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class TopicController extends Controller
{
    public function show(Forum $forum, Topic $topic): Response
    {
        $topic->incrementViews();

        $posts = $topic->posts()
            ->with(['author', 'comments.author', 'comments.replies.author'])
            ->oldest()
            ->paginate(10);

        return Inertia::render('forums/topics/show', [
            'forum' => $forum,
            'topic' => $topic->load(['author', 'forum']),
            'posts' => $posts->items(),
            'postsPagination' => Arr::except($posts->toArray(), ['data']),
        ]);
    }

    public function create(Forum $forum): Response
    {
        return Inertia::render('forums/topics/create', [
            'forum' => $forum,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request, Forum $forum): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'content' => 'required|string',
        ]);

        $topic = DB::transaction(function () use ($validated, $forum) {
            $topic = Topic::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'forum_id' => $forum->id,
                'created_by' => Auth::id(),
            ]);

            $topic->posts()->create([
                'type' => PostType::Forum,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_published' => true,
                'published_at' => now(),
                'created_by' => Auth::id(),
            ]);

            return $topic;
        });

        return to_route('forums.topics.show', compact(['forum', 'topic']));
    }

    public function destroy(Request $request, Forum $forum, Topic $topic): RedirectResponse
    {
        abort_if(
            boolean: $topic->created_by !== Auth::id() && ! $request->user()?->hasRole(Utils::getSuperAdminName()),
            code: 403,
            message: 'You are not authorized to delete this topic.'
        );

        abort_if(
            boolean: $topic->forum_id !== $forum->id,
            code: 404,
            message: 'Topic not found.'
        );

        $topic->posts()->delete();
        $topic->delete();

        return to_route('forums.show', compact('forum'))
            ->with([
                'message' => 'Topic deleted successfully.',
                'messageVariant' => 'success',
            ]);
    }
}
