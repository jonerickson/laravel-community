<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserFingerprints\Pages;

use App\Filament\Admin\Resources\UserFingerprints\UserFingerprintResource;
use Filament\Resources\Pages\ListRecords;

class ListUserFingerprints extends ListRecords
{
    protected static string $resource = UserFingerprintResource::class;

    protected ?string $subheading = 'Manage the registered user/device identifies and their access to the system.';
}
