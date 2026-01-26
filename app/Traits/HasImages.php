<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Image;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Eloquent
 */
trait HasImages
{
    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')
            ->ofMany(['id' => 'max']);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    protected static function bootHasImages(): void
    {
        static::deleting(function (Model $model): void {
            /** @var static $model */
            $model->images()->delete();
        });
    }

    protected function initializeHasImages(): void
    {
        $this->mergeFillable([
            'images',
        ]);
    }
}
