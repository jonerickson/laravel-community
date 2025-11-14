<?php

declare(strict_types=1);

namespace App\Traits;

use Laravel\Scout\Searchable as ScoutSearchable;

/**
 * @mixin \Eloquent
 */
trait Searchable
{
    use ScoutSearchable;

    public function searchableAs(): string
    {
        $table = $this->getTable();

        return "{$table}_index";
    }
}
