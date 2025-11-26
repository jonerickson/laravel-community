<?php

declare(strict_types=1);

namespace App\Listeners\Forums;

use App\Enums\PostType;
use App\Events\PostCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RemoveTopicReadsOnPostCreated implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

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
