<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasPermissions as BaseHasPermissions;
use Spatie\Permission\Traits\HasRoles as BaseHasRoles;

/**
 * @mixin Model
 */
trait HasPermissions
{
    use BaseHasPermissions;
    use BaseHasRoles;
}
