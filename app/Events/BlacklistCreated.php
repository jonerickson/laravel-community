<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Blacklist;

class BlacklistCreated
{
    public function __construct(public Blacklist $blacklist)
    {
        //
    }
}
