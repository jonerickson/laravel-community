<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TopicCreated;
use App\Models\User;

class AutoFollowCreatedTopic
{
    public function handle(TopicCreated $event): void
    {
        $topic = $event->topic;
        $user = User::find($topic->created_by);

        if ($user) {
            $topic->follow($user);
        }
    }
}
