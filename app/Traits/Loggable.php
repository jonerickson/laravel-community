<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Log;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Eloquent
 */
trait Loggable
{
    public function logs(): MorphMany
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
