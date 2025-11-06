<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PostCreated;
use App\Events\TopicCreated;
use App\Notifications\Forums\NewContentNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

class NotifyForumFollowers
{
    public function handle(TopicCreated|PostCreated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if ($event instanceof TopicCreated) {
            $content = $event->topic;
            $forum = $event->topic->forum ?? null;
        } else {
            $content = $event->post;
            $forum = $event->post->topic->forum ?? null;
        }

        if (blank($forum)) {
            return;
        }

        $followers = $forum->follows()
            ->with('author')
            ->get()
            ->pluck('author')
            ->filter(fn ($follower): bool => $follower->id !== $content->created_by);

        if ($followers->isNotEmpty()) {
            Notification::sendNow($followers, new NewContentNotification($content, $forum));
        }
    }
}
