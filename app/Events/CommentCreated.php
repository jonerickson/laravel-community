<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Comment;
use Illuminate\Foundation\Queue\Queueable;

class CommentCreated
{
    use Queueable;

    public function __construct(public Comment $comment)
    {
        //
    }
}
