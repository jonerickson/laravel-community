<?php

declare(strict_types=1);

namespace App\Traits;

use Eloquent;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin Eloquent
 */
trait HasUrl
{
    abstract public function getUrl(): ?string;

    public function url(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->getUrl()
        )->shouldCache();
    }

    protected function initializeHasUrl(): void
    {
        $this->mergeAppends([
            'url',
        ]);
    }
}
