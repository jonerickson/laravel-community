<?php

declare(strict_types=1);

namespace App\Traits;

use Eloquent;

/**
 * @mixin Eloquent
 */
trait HasColor
{
    protected function initializeHasColor(): void
    {
        $this->mergeFillable([
            'color',
        ]);
    }
}
