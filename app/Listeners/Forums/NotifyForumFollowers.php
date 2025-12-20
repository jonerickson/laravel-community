<?php

declare(strict_types=1);

namespace App\Listeners\Forums;

use App\Events\TopicCreated;
use App\Models\Follow;
use App\Notifications\Forums\NewContentNotification;
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

        $followers = $forum->follows()
            ->with('author')
            ->get()
            ->pluck('author')
            ->filter(fn (Follow $follower): bool => $follower->id !== $content->created_by);

        if ($followers->isNotEmpty()) {
            Notification::sendNow($followers, new NewContentNotification($content, $forum));
        }
    }
}
