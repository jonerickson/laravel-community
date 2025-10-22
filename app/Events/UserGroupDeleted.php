<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\UserGroup;

class UserGroupDeleted
{
    public function __construct(public UserGroup $userGroup)
    {
        //
    }
}
