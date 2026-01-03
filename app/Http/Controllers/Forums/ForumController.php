<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Data\ForumData;
use App\Data\PaginatedData;
use App\Data\TopicData;
use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Topic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class ForumController extends Controller
{
    use AuthorizesRequests;

    public function show(Forum $forum): Response
    {
        $this->authorize('view', $forum);

        $forum->loadMissing(['category', 'parent.parent.parent', 'groups']);
        $forum->loadCount(['followers']);

        return Inertia::render('forums/show', [
            'forum' => ForumData::from($forum),
            'children' => Inertia::defer(fn (): Collection => ForumData::collect($forum
                ->children()
                ->with(['latestTopic.author', 'latestTopic.lastPost'])
                ->withCount(['topics', 'posts'])
                ->get()
                ->filter(fn (Forum $forum) => Gate::check('view', $forum))
                ->values()), 'children'),
            'topics' => Inertia::defer(function () use ($forum): PaginatedData {
                $topics = $forum
                    ->topics()
                    ->latestActivity()
                    ->with(['author', 'lastPost.author'])
                    ->withCount(['posts', 'views'])
                    ->paginate();

                $filteredTopics = $topics
                    ->collect()
                    ->filter(fn (Topic $topic) => Gate::check('view', $topic))
                    ->values();

                return PaginatedData::from(TopicData::collect($topics->setCollection($filteredTopics), PaginatedDataCollection::class)->items());
            }, 'topics'),
        ]);
    }
}
