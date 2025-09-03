<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupSaving
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group)
    {
        //
    }
}
