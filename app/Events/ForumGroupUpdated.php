<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ForumGroup;

class ForumGroupUpdated
{
    public function __construct(public ForumGroup $forumGroup)
    {
        //
    }
}
