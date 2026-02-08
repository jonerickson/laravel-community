<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Dispute;

class DisputeCreated
{
    public function __construct(
        public Dispute $dispute,
    ) {}
}
