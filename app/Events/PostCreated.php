<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Post;

class PostCreated
{
    public function __construct(
        public Post $post
    ) {}
}
