<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Data\ForumData;
use App\Data\TopicData;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class ForumController extends Controller
{
    use AuthorizesRequests;

    /**
     * @throws AuthorizationException
     */
    public function show(Forum $forum): Response
    {
        $this->authorize('view', $forum);

        $forum->setRelation('children', $forum
            ->loadMissing(['children' => fn (HasMany|Forum $query) => $query->active()->ordered()])
            ->loadMissing(['children.latestTopics.posts.reads', 'children.latestTopics.posts.views', 'children.latestTopics.posts.likes', 'children.latestTopics.posts.comments', 'children.latestTopics.author', 'children.latestTopics.lastPost.author', 'parent', 'category'])
            ->children
            ->filter(fn (Forum $child) => Gate::check('view', $child))
            ->values()
        );

        $topics = TopicData::collect($forum
            ->loadMissing(['topics' => fn (HasMany|Topic $query) => $query->latestActivity()])
            ->loadMissing(['topics.author', 'topics.lastPost.author', 'topics.posts.reads', 'topics.posts.views', 'topics.posts.likes', 'topics.posts.comments'])
            ->topics
            ->filter(fn (Topic $topic) => Gate::check('view', $topic))
            ->values()
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('forums/show', [
            'forum' => ForumData::from($forum),
            'topics' => Inertia::scroll(fn () => $topics->items()),
        ]);
    }
}
