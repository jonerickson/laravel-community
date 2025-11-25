<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Topic;

class TopicUpdated
{
    public function __construct(
        public Topic $topic
    ) {}
}
