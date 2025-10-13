<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Note;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Eloquent
 */
trait HasNotes
{
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }
}
