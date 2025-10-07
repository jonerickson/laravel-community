<?php

declare(strict_types=1);

namespace App\Traits;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Eloquent
 */
trait Pinnable
{
    public function scopePinned(Builder $query): void
    {
        $query->where('is_pinned', true);
    }

    public function scopeNotPinned(Builder $query): void
    {
        $query->where('is_pinned', false);
    }

    public function pin(): static
    {
        return tap($this)->update(['is_pinned' => true]);
    }

    public function unpin(): static
    {
        return tap($this)->update(['is_pinned' => false]);
    }

    public function togglePin(): static
    {
        return tap($this)->update(['is_pinned' => ! $this->is_pinned]);
    }

    protected static function bootPinnable(): void
    {
        static::creating(function (Model $model): void {
            if (! isset($model->is_pinned)) {
                $model->fill([
                    'is_pinned' => false,
                ]);
            }
        });
    }

    protected function initializePinnable(): void
    {
        $this->mergeCasts([
            'is_pinned' => 'boolean',
        ]);

        $this->mergeFillable([
            'is_pinned',
        ]);
    }
}
