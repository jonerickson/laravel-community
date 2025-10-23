<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\UserIntegration;

class UserIntegrationCreated
{
    public function __construct(public UserIntegration $integration)
    {
        //
    }
}
