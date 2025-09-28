<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Group;
use Illuminate\Foundation\Queue\Queueable;

class GroupSaving
{
    use Queueable;

    public function __construct(public Group $group)
    {
        //
    }
}
