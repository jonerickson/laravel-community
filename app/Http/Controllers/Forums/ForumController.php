<?php

declare(strict_types=1);

namespace App\Http\Controllers\Forums;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use Inertia\Inertia;
use Inertia\Response;

class ForumController extends Controller
{
    public function index(): Response
    {
        $forums = Forum::active()
            ->ordered()
            ->withCount(['topics'])
            ->with(['latestTopics' => function ($query) {
                $query->with(['author', 'lastPost.author'])
                    ->limit(3);
            }])
            ->get();

        return Inertia::render('forums/index', [
            'forums' => $forums,
        ]);
    }

    public function show(Forum $forum): Response
    {
        $topics = $forum->topics()
            ->with(['author', 'lastPost.author'])
            ->latestActivity()
            ->paginate(20);

        return Inertia::render('forums/show', [
            'forum' => $forum,
            'topics' => $topics,
        ]);
    }
}
