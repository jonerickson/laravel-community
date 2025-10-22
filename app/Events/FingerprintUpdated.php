<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Fingerprint;

class FingerprintUpdated
{
    public function __construct(public Fingerprint $fingerprint) {}
}
