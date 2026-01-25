<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Actions\Forums\DeleteTopicAction;
use App\Data\ForumCategoryData;
use App\Data\ForumData;
use App\Data\PaginatedData;
use App\Data\PostData;
use App\Data\RecentViewerData;
use App\Data\TopicData;
use App\Enums\PostType;
use App\Events\TopicCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Forums\StoreTopicRequest;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Throwable;

class TopicController extends Controller
{
    use AuthorizesRequests;

    public function create(Forum $forum): Response
    {
        $this->authorize('view', $forum);
        $this->authorize('create', [Topic::class, $forum]);

        $forum->loadMissing(['category', 'parent.parent.parent']);

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
        $this->authorize('create', [Topic::class, $forum]);

        $topic = DB::transaction(fn () => Event::defer(function () use ($request, $forum): Topic {
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
        }, [TopicCreated::class]));

        return to_route('forums.topics.show', ['forum' => $forum, 'topic' => $topic])
            ->with([
                'message' => 'Your topic was successfully created.',
                'messageVariant' => 'success',
            ]);
    }

    public function show(Request $request, Forum $forum, Topic $topic): Response|RedirectResponse
    {
        $this->authorize('view', $forum);
        $this->authorize('view', $topic);

        $forum->loadMissing(['parent.parent.parent', 'category', 'follows']);

        $topic->incrementViews();
        $topic->loadMissing(['author.groups', 'forum.category']);
        $topic->loadCount(['posts', 'views', 'followers']);

        $posts = $topic
            ->posts()
            ->latestActivity()
            ->with(['author.groups' => function (BelongsToMany|Group $query): void {
                $query->active()->visible()->ordered();
            }])
            ->with(['likes'])
            ->withCount(['likes'])
            ->paginate();

        $filteredPosts = $posts
            ->collect()
            ->filter(fn (Post $post) => Gate::check('view', $post))
            ->values();

        $currentPage = $request->integer('page', 1);

        if ($posts->isEmpty() && $currentPage > 1) {
            return redirect()->to(
                request()->fullUrlWithQuery(['page' => $posts->lastPage()])
            );
        }

        return Inertia::render('forums/topics/show', [
            'forum' => ForumData::from($forum),
            'topic' => TopicData::from($topic),
            'posts' => Inertia::defer(fn (): PaginatedData => PaginatedData::from(PostData::collect($posts->setCollection($filteredPosts), PaginatedDataCollection::class)->items()), 'posts'),
            'categories' => Inertia::defer(fn (): Collection => ForumCategoryData::collect(ForumCategoryData::collect(ForumCategory::query()->with(['forums' => fn (HasMany|Forum $query) => $query->whereNull('parent_id')->recursiveChildren()])->get())), 'categories'),
            'recentViewers' => Inertia::defer(fn (): array => RecentViewerData::collect($topic->getRecentViewers()), 'recentViewers'),
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
