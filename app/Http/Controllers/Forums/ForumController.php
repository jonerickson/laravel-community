<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Data\ForumData;
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

        $forum->load([
            'category',
            'parent',
            'children' => function ($query): void {
                $query
                    ->active()
                    ->ordered()
                    ->withCount(['topics', 'posts'])
                    ->with(['latestTopics' => function ($subQuery): void {
                        $subQuery
                            ->with(['author', 'lastPost.author'])
                            ->limit(3);
                    }]);
            },
        ]);

        $forum->setRelation(
            'children',
            $forum->children
                ->filter(fn (Forum $child) => Gate::check('view', $child))
                ->map(function (Forum $child): Forum {
                    $child->setRelation(
                        'latestTopics',
                        $child->latestTopics->filter(fn (Topic $topic) => Gate::check('view', $topic))->values()
                    );

                    return $child;
                })
                ->values()
        );

        $topics = TopicData::collect($forum
            ->topics()
            ->with(['author', 'lastPost.author'])
            ->latestActivity()
            ->get()
            ->filter(fn (Topic $topic) => Gate::check('view', [$topic, $forum]))
            ->values()
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('forums/show', [
            'forum' => ForumData::from($forum),
            'topics' => Inertia::scroll(fn () => $topics->items()),
        ]);
    }
}
