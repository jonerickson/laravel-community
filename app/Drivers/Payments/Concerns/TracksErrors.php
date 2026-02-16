<?php

declare(strict_types=1);

namespace App\Drivers\Payments\Concerns;

use App\Data\PaymentErrorData;

trait TracksErrors
{
    public protected(set) ?PaymentErrorData $lastError = null;
}
