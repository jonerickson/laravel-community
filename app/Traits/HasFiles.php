<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\File;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Eloquent
 */
trait HasFiles
{
    public function file(): MorphOne
    {
        return $this->morphOne(File::class, 'resource');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'resource');
    }
}
