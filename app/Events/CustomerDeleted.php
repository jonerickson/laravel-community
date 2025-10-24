<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;

class CustomerDeleted
{
    public function __construct(public User $user)
    {
        //
    }
}
