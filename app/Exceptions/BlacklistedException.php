<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Blacklist;
use Exception;

class BlacklistedException extends Exception
{
    public function __construct(
        public Blacklist $blacklist,
    ) {
        parent::__construct(
            message: 'Your account has been blacklisted.',
            code: 403,
        );
    }
}
