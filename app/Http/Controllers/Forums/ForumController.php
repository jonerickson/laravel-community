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
            ->loadMissing(['children' => fn (HasMany|Forum $query) => $query->withCount(['topics', 'posts'])->active()->ordered()])
            ->loadMissing(['children.latestTopic.author', 'category', 'parent'])
            ->children
            ->filter(fn (Forum $child) => Gate::check('view', $child))
            ->values()
        );

        $topics = TopicData::collect($forum
            ->loadMissing(['topics' => fn (HasMany|Topic $query) => $query->withCount(['posts', 'views'])->latestActivity()])
            ->loadMissing(['topics.author', 'topics.lastPost.author'])
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
