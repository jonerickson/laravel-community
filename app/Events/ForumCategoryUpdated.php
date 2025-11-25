<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ForumCategory;

class ForumCategoryUpdated
{
    public function __construct(
        public ForumCategory $forumCategory,
    ) {}
}
