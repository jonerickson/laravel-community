<?php

declare(strict_types=1);

namespace App\Traits;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public function lock(): static
    {
        return tap($this)->update(['is_locked' => true]);
    }

    public function unlock(): static
    {
        return tap($this)->update(['is_locked' => false]);
    }

    public function toggleLock(): static
    {
        return tap($this)->update(['is_locked' => ! $this->is_locked]);
    }

    protected static function bootLockable(): void
    {
        static::creating(function (Model $model): void {
            if (! isset($model->is_locked)) {
                $model->fill([
                    'is_locked' => false,
                ]);
            }
        });
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
