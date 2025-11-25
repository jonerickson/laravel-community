<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Forum;

class ForumUpdated
{
    public function __construct(
        public Forum $forum
    ) {}
}
