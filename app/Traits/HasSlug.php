<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\Sluggable;
use Eloquent;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * @mixin Eloquent
 */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function (Sluggable $model): void {
            if (blank($model->getAttribute('slug'))) {
                $slug = $model->generateSlug();
                $slugExists = $model->newModelQuery()->where('slug', $slug)->exists();

                $model->forceFill([
                    'slug' => Str::of($slug)
                        ->when($slugExists, fn (Stringable $str) => $str->append('-')->append(Str::random(8)))
                        ->slug()
                        ->toString(),
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
