<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Comment;

class CommentDeleted
{
    public function __construct(public Comment $comment)
    {
        //
    }
}
