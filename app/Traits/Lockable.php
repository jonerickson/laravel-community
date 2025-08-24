<?php

declare(strict_types=1);

namespace App\Traits;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin Eloquent
 */
trait Lockable
{
    public function scopeLocked(Builder $query): void
    {
        $query->where('is_locked', true);
    }

    public function scopeUnlocked(Builder $query): void
    {
        $query->where('is_locked', false);
    }

    public function lock(): bool
    {
        return $this->update(['is_locked' => true]);
    }

    public function unlock(): bool
    {
        return $this->update(['is_locked' => false]);
    }

    public function toggleLock(): bool
    {
        return $this->update(['is_locked' => ! $this->is_locked]);
    }

    protected function initializeLockable(): void
    {
        $this->mergeCasts([
            'is_locked' => 'boolean',
        ]);

        $this->mergeFillable([
            'is_locked',
        ]);
    }
}
