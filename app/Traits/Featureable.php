<?php

declare(strict_types=1);

namespace App\Traits;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Eloquent
 */
trait Featureable
{
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function scopeNotFeatured(Builder $query): void
    {
        $query->where('is_featured', false);
    }

    protected static function bootFeatureable(): void
    {
        static::creating(function (Model $model): void {
            if (! isset($model->is_featured)) {
                $model->fill([
                    'is_featured' => false,
                ]);
            }
        });
    }

    protected function initializeFeatureable(): void
    {
        $this->mergeFillable([
            'is_featured',
        ]);

        $this->mergeCasts([
            'is_featured' => 'boolean',
        ]);
    }
}
