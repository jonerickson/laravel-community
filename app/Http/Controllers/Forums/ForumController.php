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
use Illuminate\Support\Arr;
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

        $topics = TopicData::collect($forum
            ->topics()
            ->with(['author', 'lastPost.author'])
            ->latestActivity()
            ->get()
            ->filter(fn (Topic $topic) => Gate::check('view', [$topic, $forum]))
            ->all(), PaginatedDataCollection::class);

        return Inertia::render('forums/show', [
            'forum' => ForumData::from($forum),
            'topics' => Inertia::merge(fn () => $topics->items()->items()),
            'topicsPagination' => PaginatedData::from(Arr::except($topics->items()->toArray(), ['data'])),
        ]);
    }
}
