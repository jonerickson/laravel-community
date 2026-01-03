<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ForumCategoryGroup;

class ForumCategoryGroupCreated
{
    public function __construct(public ForumCategoryGroup $forumCategoryGroup)
    {
        //
    }
}
