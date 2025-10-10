<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\UserGroup;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserGroupCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public UserGroup $userGroup)
    {
        //
    }
}
