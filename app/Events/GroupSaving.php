<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Group;

class GroupSaving
{
    public function __construct(public Group $group)
    {
        //
    }
}
