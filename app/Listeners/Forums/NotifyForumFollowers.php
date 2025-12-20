<?php

declare(strict_types=1);

namespace App\Listeners\Forums;

use App\Events\TopicCreated;
use App\Models\Forum;
use App\Notifications\Forums\NewContentNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

class NotifyForumFollowers
{
    public function handle(TopicCreated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $content = $event->topic;
        $forum = $event->topic->forum ?? null;

        if (blank($forum)) {
            return;
        }

        $forums = $this->getAllForumsInHierarchy($forum);

        $followers = $forums
            ->flatMap(fn ($forum) => $forum->follows()->with('author')->get())
            ->pluck('author')
            ->unique('id')
            ->filter(fn ($follower): bool => $follower?->id !== $content->created_by);

        if ($followers->isNotEmpty()) {
            Notification::sendNow($followers, new NewContentNotification($content, $forum));
        }
    }

    private function getAllForumsInHierarchy(Forum $forum): Collection
    {
        $forums = collect([$forum]);
        $currentForum = $forum;

        while ($currentForum->parent_id !== null) {
            $currentForum = $currentForum->parent;
            $forums->push($currentForum);
        }

        return $forums;
    }
}
