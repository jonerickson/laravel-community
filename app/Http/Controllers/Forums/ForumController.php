<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Data\ForumData;
use App\Data\PaginatedData;
use App\Data\TopicData;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Auth\Access\AuthorizationException;
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

        $forum
            ->loadMissing(['category', 'parent'])
            ->setRelation('children', $forum
                ->children()
                ->with(['latestTopic.author'])
                ->withCount(['topics', 'posts'])
                ->get()
                ->filter(fn (Forum $child) => Gate::check('view', $child))
                ->values()
            );

        $topics = $forum
            ->topics()
            ->with(['author', 'lastPost.author', 'posts.reads'])
            ->withCount(['posts', 'views'])
            ->paginate();

        $filteredTopics = $topics
            ->collect()
            ->filter(fn (Topic $topic) => Gate::check('view', $topic))
            ->values();

        return Inertia::render('forums/show', [
            'forum' => ForumData::from($forum),
            'topics' => PaginatedData::from(TopicData::collect($topics->setCollection($filteredTopics), PaginatedDataCollection::class)->items()),
        ]);
    }
}
