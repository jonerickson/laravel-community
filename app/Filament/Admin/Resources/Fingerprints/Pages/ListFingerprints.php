<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Fingerprints\Pages;

use App\Filament\Admin\Resources\Fingerprints\FingerprintResource;
use Filament\Resources\Pages\ListRecords;

class ListFingerprints extends ListRecords
{
    protected static string $resource = FingerprintResource::class;

    protected ?string $subheading = 'Manage the registered user/device identities and their access to the system.';
}
