<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public function activate(): static
    {
        return tap($this)->update([
            'is_active' => true,
        ]);
    }

    public function deactivate(): static
    {
        return tap($this)->update([
            'is_active' => false,
        ]);
    }

    protected static function bootActivateable(): void
    {
        static::creating(function (Model $model): void {
            if (! isset($model->is_active)) {
                $model->fill([
                    'is_active' => true,
                ]);
            }
        });
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
