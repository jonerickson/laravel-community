<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Fingerprint;
use Exception;

class BannedException extends Exception
{
    public function __construct(public Fingerprint $fingerprint)
    {
        parent::__construct(
            message: 'Your account has been banned.',
            code: 403,
        );
    }
}
