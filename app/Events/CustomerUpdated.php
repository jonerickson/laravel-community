<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerUpdated implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user)
    {
        //
    }
}
