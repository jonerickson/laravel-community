<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PostCreated;
use App\Notifications\Forums\NewContentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyTopicFollowers implements ShouldQueue
{
    use Queueable;

    public function handle(PostCreated $event): void
    {
        $post = $event->post;
        $topic = $post->topic;

        if (blank($topic)) {
            return;
        }

        $followers = $topic->follows()
            ->with('author')
            ->get()
            ->pluck('author')
            ->filter(fn ($follower): bool => $follower->id !== $post->created_by);

        if ($followers->isNotEmpty()) {
            Notification::send($followers, new NewContentNotification($post, $topic));
        }
    }
}
