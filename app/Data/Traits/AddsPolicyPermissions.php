<?php

declare(strict_types=1);

namespace App\Data\Traits;

use App\Data\Normalizers\Models\PolicyPermissionsNormalizer;
use App\Data\PolicyPermissionData;
use Spatie\LaravelData\Data;

/**
 * @mixin Data
 */
trait AddsPolicyPermissions
{
    public PolicyPermissionData $policyPermissions;

    public static function normalizers(): array
    {
        return [
            PolicyPermissionsNormalizer::class,
            ...config('data.normalizers'),
        ];
    }
}
