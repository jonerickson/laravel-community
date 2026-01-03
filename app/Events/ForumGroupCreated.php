<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ForumGroup;

class ForumGroupCreated
{
    public function __construct(public ForumGroup $forumGroup)
    {
        //
    }
}
