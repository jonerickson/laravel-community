<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Eloquent
 */
trait Approvable
{
    public function scopeApproved(Builder $query): void
    {
        $query->where('is_approved', true);
    }

    public function scopeUnapproved(Builder $query): void
    {
        $query->where('is_approved', false);
    }

    public function approve(): static
    {
        return tap($this)->update(['is_approved' => true]);
    }

    public function unapprove(): static
    {
        return tap($this)->update(['is_approved' => false]);
    }

    protected static function bootApprovable(): void
    {
        static::creating(function (Model $model): void {
            if (! isset($model->is_approved)) {
                $model->fill([
                    'is_approved' => true,
                ]);
            }
        });
    }

    protected function initializeApprovable(): void
    {
        $this->mergeFillable([
            'is_approved',
        ]);

        $this->mergeCasts([
            'is_approved' => 'boolean',
        ]);
    }
}
