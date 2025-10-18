<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Eloquent
 */
trait Visible
{
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    public function scopeHidden(Builder $query): void
    {
        $query->where('is_visible', false);
    }

    public function show(): static
    {
        return tap($this)->update(['is_visible' => true]);
    }

    public function hide(): static
    {
        return tap($this)->update(['is_visible' => false]);
    }

    protected static function bootVisible(): void
    {
        static::creating(function (Model $model): void {
            if (! isset($model->is_visible)) {
                $model->fill([
                    'is_visible' => true,
                ]);
            }
        });
    }

    protected function initializeVisible(): void
    {
        $this->mergeFillable([
            'is_visible',
        ]);

        $this->mergeCasts([
            'is_visible' => 'boolean',
        ]);
    }
}
