<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Post;
use Illuminate\Foundation\Queue\Queueable;

class PostCreated
{
    use Queueable;

    public function __construct(
        public Post $post
    ) {}
}
