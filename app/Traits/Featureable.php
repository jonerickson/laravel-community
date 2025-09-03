<?php

declare(strict_types=1);

namespace App\Traits;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin Eloquent
 */
trait Featureable
{
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    protected function initializeFeatureable()
    {
        $this->mergeFillable([
            'is_featured',
        ]);

        $this->mergeCasts([
            'is_featured' => 'boolean',
        ]);
    }
}
