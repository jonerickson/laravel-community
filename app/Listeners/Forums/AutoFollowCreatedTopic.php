<?php

declare(strict_types=1);

namespace App\Listeners\Forums;

use App\Events\TopicCreated;
use App\Models\User;
use Illuminate\Support\Facades\App;

class AutoFollowCreatedTopic
{
    public function handle(TopicCreated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $topic = $event->topic;
        $user = User::find($topic->created_by);

        if ($user) {
            $topic->follow($user);
        }
    }
}
