<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Eloquent
 */
trait Activateable
{
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): void
    {
        $query->where('is_active', false);
    }

    protected function initializeActivateable(): void
    {
        $this->mergeCasts([
            'is_active' => 'boolean',
        ]);

        $this->mergeFillable([
            'is_active',
        ]);
    }
}
