<?php

declare(strict_types=1);

namespace App\Listeners\Forums;

use App\Enums\PostType;
use App\Events\PostCreated;

class RemoveTopicReadsOnPostCreated
{
    public function handle(PostCreated $event): void
    {
        if ($event->post->type !== PostType::Forum) {
            return;
        }

        $event
            ->post
            ->topic
            ->reads()
            ->where('created_by', '<>', $event->post->created_by)
            ->delete();
    }
}
