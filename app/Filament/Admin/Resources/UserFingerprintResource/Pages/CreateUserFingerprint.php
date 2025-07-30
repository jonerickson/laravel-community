<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserFingerprintResource\Pages;

use App\Filament\Admin\Resources\UserFingerprintResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserFingerprint extends CreateRecord
{
    protected static string $resource = UserFingerprintResource::class;
}
