<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PostCreated;
use App\Events\TopicCreated;
use App\Notifications\Forums\NewContentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyForumFollowers implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(TopicCreated|PostCreated $event): void
    {
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
            Notification::send($followers, new NewContentNotification($content, $forum));
        }
    }
}
