<?php

declare(strict_types=1);

namespace App\Listeners\Forums;

use App\Events\PostCreated;
use App\Models\User;
use App\Notifications\Forums\NewContentNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

class NotifyTopicFollowers
{
    public function handle(PostCreated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $post = $event->post;
        $topic = $post->topic;

        if (blank($topic)) {
            return;
        }

        $followers = $topic->follows()
            ->with('author')
            ->get()
            ->pluck('author')
            ->filter(fn (User $follower): bool => $follower->id !== $post->created_by);

        if ($followers->isNotEmpty()) {
            Notification::sendNow($followers, new NewContentNotification($post, $topic));
        }
    }
}
