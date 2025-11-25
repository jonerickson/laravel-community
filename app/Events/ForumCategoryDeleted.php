<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ForumCategory;

class ForumCategoryDeleted
{
    public function __construct(
        public ForumCategory $forumCategory,
    ) {}
}
