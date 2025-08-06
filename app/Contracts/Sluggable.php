<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
interface Sluggable
{
    public function generateSlug(): ?string;
}
