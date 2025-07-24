<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function show(Forum $forum, Topic $topic): Response
    {
        $topic->incrementViews();

        $posts = $topic->posts()
            ->with(['author', 'comments.author', 'comments.replies.author'])
            ->oldest()
            ->paginate(10);

        return Inertia::render('forums/topic', [
            'forum' => $forum,
            'topic' => $topic->load(['author', 'forum']),
            'posts' => $posts,
            'canReply' => Auth::user()?->can('reply', $topic) ?? false,
        ]);
    }

    public function create(Forum $forum): Response
    {
        return Inertia::render('forums/create-topic', [
            'forum' => $forum,
        ]);
    }

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
                'last_reply_at' => now(),
            ]);

            $post = Post::create([
                'post_type' => PostType::Forum,
                'topic_id' => $topic->id,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_published' => true,
                'published_at' => now(),
                'created_by' => Auth::id(),
            ]);

            $topic->updateLastReply($post);

            return $topic;
        });

        return redirect()->route('forums.topics.show', [$forum, $topic]);
    }

    public function reply(Request $request, Forum $forum, Topic $topic): RedirectResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $post = Post::create([
            'post_type' => PostType::Forum,
            'topic_id' => $topic->id,
            'title' => 'Re: '.$topic->title,
            'content' => $validated['content'],
            'is_published' => true,
            'published_at' => now(),
            'created_by' => Auth::id(),
        ]);

        $topic->updateLastReply($post);

        return back();
    }
}
