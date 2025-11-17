<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\App;
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
        $environment = App::environment();

        return sprintf('%s_%s_index', $table, $environment);
    }
}
