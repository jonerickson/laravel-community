<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Actions\Forums\DeleteTopicAction;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class TopicController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function show(Forum $forum, Topic $topic): Response
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);

        $topic->incrementViews();

        $forum->loadMissing('category');

        /** @var LengthAwarePaginator $posts */
        $posts = $topic
            ->posts()
            ->with(['author', 'comments.author', 'comments.replies.author', 'reports'])
            ->latestActivity()
            ->paginate(10);

        $posts->setCollection(
            collection: $posts->getCollection()->filter(fn (Post $post) => Gate::check('view', $post))
        );

        return Inertia::render('forums/topics/show', [
            'forum' => $forum,
            'topic' => $topic->load(['author', 'forum']),
            'posts' => Inertia::merge(fn () => $posts->items()),
            'postsPagination' => Arr::except($posts->toArray(), ['data']),
            'recentViewers' => Inertia::defer(fn (): array => $topic->getRecentViewers()),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function create(Forum $forum): Response
    {
        $this->authorize('view', $forum);
        $this->authorize('create', Topic::class);

        $forum->loadMissing('category');

        return Inertia::render('forums/topics/create', [
            'forum' => $forum,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request, Forum $forum): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('create', Topic::class);

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
            ]);

            $topic->posts()->create([
                'type' => PostType::Forum,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_published' => true,
                'published_at' => now(),
            ]);

            return $topic;
        });

        return to_route('forums.topics.show', [
            'forum' => $forum,
            'topic' => $topic,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Forum $forum, Topic $topic): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('delete', $topic);

        DeleteTopicAction::execute($topic, $forum);

        return to_route('forums.show', ['forum' => $forum])
            ->with([
                'message' => 'Topic deleted successfully.',
                'messageVariant' => 'success',
            ]);
    }
}
