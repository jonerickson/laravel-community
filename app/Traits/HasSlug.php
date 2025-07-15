<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\Sluggable;
use Eloquent;

/**
 * @mixin Eloquent
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function (Sluggable $model) {
            if (blank($model->getAttribute('slug'))) {
                $model->forceFill([
                    'slug' => $model->generateSlug(),
                ]);
            }
        });
    }

    protected function initializeHasSlug(): void
    {
        $this->mergeFillable([
            'slug',
        ]);
    }
}
