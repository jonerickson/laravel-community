<?php

namespace App\Traits;

use App\Contracts\Sluggable;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
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
