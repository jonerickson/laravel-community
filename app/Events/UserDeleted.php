<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Queue\Queueable;

class UserDeleted
{
    use Queueable;

    public function __construct(public User $user)
    {
        //
    }
}
