<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Blacklist;
use App\Models\User;

class BlacklistMatch
{
    public function __construct(
        public string $content,
        public Blacklist $blacklist,
        public ?User $user = null
    ) {
        //
    }
}
