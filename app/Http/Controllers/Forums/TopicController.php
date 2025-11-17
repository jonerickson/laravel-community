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
        $this->authorize('view', $forum);
        $this->authorize('create', Topic::class);

        $forum->loadMissing(['category', 'parent']);

        return Inertia::render('forums/topics/create', [
            'forum' => ForumData::from($forum),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreTopicRequest $request, Forum $forum): RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('create', Topic::class);

        $topic = DB::transaction(function () use ($request, $forum) {
            $topic = Topic::create([
                'title' => $request->validated('title'),
                'forum_id' => $forum->id,
            ]);

            $topic->posts()->create([
                'type' => PostType::Forum,
                'title' => $request->validated('title'),
                'content' => $request->validated('content'),
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
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);

        $forum->loadMissing(['parent', 'category']);

        $topic->incrementViews();
        $topic->loadMissing(['author']);
        $topic->loadCount(['posts', 'views']);

        $posts = $topic
            ->posts()
            ->latestActivity()
            ->with(['author.groups', 'reads', 'views', 'likes.author', 'comments'])
            ->paginate();

        $filteredPosts = $posts
            ->collect()
            ->filter(fn (Post $post) => Gate::check('view', $post))
            ->values();

        return Inertia::render('forums/topics/show', [
            'forum' => ForumData::from($forum),
            'topic' => TopicData::from($topic),
            'posts' => Inertia::defer(fn (): PaginatedData => PaginatedData::from(PostData::collect($posts->setCollection($filteredPosts), PaginatedDataCollection::class)->items()), 'posts'),
            'recentViewers' => Inertia::defer(fn (): array => RecentViewerData::collect($topic->getRecentViewers()), 'viewers'),
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
            ->with('message', 'The topic was successfully deleted.');
    }
}
