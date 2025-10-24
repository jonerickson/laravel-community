<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;

class CustomerUpdated
{
    public function __construct(public User $user)
    {
        //
    }
}
