<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\UserSocial;
use Illuminate\Foundation\Queue\Queueable;

class UserSocialCreated
{
    use Queueable;

    public function __construct(public UserSocial $social)
    {
        //
    }
}
