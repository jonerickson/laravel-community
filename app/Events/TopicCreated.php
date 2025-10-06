<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Topic;
use Illuminate\Foundation\Queue\Queueable;

class TopicCreated
{
    use Queueable;

    public function __construct(
        public Topic $topic
    ) {}
}
