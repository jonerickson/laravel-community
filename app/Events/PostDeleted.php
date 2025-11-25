<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Post;

class PostDeleted
{
    public function __construct(
        public Post $post
    ) {}
}
