<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Fingerprint;
use Illuminate\Foundation\Queue\Queueable;

class FingerprintUpdated
{
    use Queueable;

    public function __construct(public Fingerprint $fingerprint) {}
}
