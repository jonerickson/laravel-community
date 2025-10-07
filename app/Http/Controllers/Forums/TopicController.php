<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Actions\Forums\DeleteTopicAction;
use App\Data\ForumData;
use App\Data\PaginatedData;
use App\Data\PostData;
use App\Data\RecentViewerData;
use App\Data\TopicData;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Forums\StoreTopicRequest;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Throwable;

class TopicController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function create(Forum $forum): Response
    {
        $this->authorize('create', [Topic::class, $forum]);

        return Inertia::render('forums/topics/create', [
            'forum' => ForumData::from($forum),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreTopicRequest $request, Forum $forum): RedirectResponse
    {
        $this->authorize('create', [Topic::class, $forum]);

        $validated = $request->validated();

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
            ]);

            return $topic;
        });

        return to_route('forums.topics.show', [
            'forum' => $forum,
            'topic' => $topic,
        ])->with([
            'message' => 'Your topic was successfully created.',
            'messageVariant' => 'success',
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Forum $forum, Topic $topic): Response
    {
        $this->authorize('view', [$topic, $forum]);

        $topic->incrementViews();

        $posts = PostData::collect($topic
            ->posts()
            ->with(['author', 'comments.author', 'comments.replies.author', 'reports'])
            ->latestActivity()
            ->paginate(10)
            ->filter(fn (Post $post) => Gate::check('view', [$post, $forum, $topic]))
            ->values()
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('forums/topics/show', [
            'forum' => ForumData::from($forum),
            'topic' => TopicData::from($topic),
            'posts' => Inertia::merge(fn () => $posts->items()->items()),
            'postsPagination' => PaginatedData::from(Arr::except($posts->items()->toArray(), ['data'])),
            'recentViewers' => Inertia::defer(fn (): array => RecentViewerData::collect($topic->getRecentViewers())),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Forum $forum, Topic $topic): RedirectResponse
    {
        $this->authorize('delete', [$topic, $forum]);

        DeleteTopicAction::execute($topic, $forum);

        return to_route('forums.show', ['forum' => $forum])
            ->with('message', 'The topic was successfully deleted.');
    }
}
