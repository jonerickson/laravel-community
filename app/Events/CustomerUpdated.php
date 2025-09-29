<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CustomerUpdated implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
        //
    }
}
