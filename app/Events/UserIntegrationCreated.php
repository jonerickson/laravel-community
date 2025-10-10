<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\UserIntegration;
use Illuminate\Foundation\Queue\Queueable;

class UserIntegrationCreated
{
    use Queueable;

    public function __construct(public UserIntegration $social)
    {
        //
    }
}
