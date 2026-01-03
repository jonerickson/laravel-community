<?php

declare(strict_types=1);

namespace App\Data\Traits;

use App\Data\ForumPermissionData;
use App\Data\Normalizers\Forums\ForumPermissionsNormalizer;
use Spatie\LaravelData\Data;

/**
 * @mixin Data
 */
trait AddsForumPermissions
{
    public ForumPermissionData $forumPermissions;

    public static function normalizers(): array
    {
        return [
            ForumPermissionsNormalizer::class,
            ...config('data.normalizers'),
        ];
    }
}
