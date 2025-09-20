<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * @mixin Data
 */
trait HasPermissions
{
    public PermissionData $permissions;

    public static function from(mixed ...$payloads): static
    {
        return parent::from(...$payloads)->additional([
            'permissions' => PermissionData::from(...$payloads)->toArray(),
        ]);
    }
}
