<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
interface Sluggable
{
    public function generateSlug(): string;
}
