<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Dispute;

class DisputeUpdated
{
    public function __construct(
        public Dispute $dispute,
    ) {}
}
