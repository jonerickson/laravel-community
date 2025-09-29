<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CustomerDeleted implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
        //
    }
}
