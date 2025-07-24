<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * @mixin \Eloquent
 */
trait HasMetadata
{
    protected function initializeHasMetadata(): void
    {
        $this->mergeCasts([
            'metadata' => 'array',
        ]);

        $this->mergeFillable([
            'metadata',
        ]);
    }
}
