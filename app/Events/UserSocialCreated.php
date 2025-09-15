<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\UserSocial;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserSocialCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public UserSocial $social)
    {
        //
    }
}
