<?php

declare(strict_types=1);

namespace App\Data\Traits;

use App\Data\Normalizers\ModelPermissionNormalizer;
use App\Data\PermissionData;
use Spatie\LaravelData\Data;

/**
 * @mixin Data
 */
trait HasDataPermissions
{
    public PermissionData $permissions;

    public static function normalizers(): array
    {
        return [
            ModelPermissionNormalizer::class,
        ];
    }
}
