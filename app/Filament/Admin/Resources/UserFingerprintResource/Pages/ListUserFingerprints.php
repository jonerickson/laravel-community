<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserFingerprintResource\Pages;

use App\Filament\Admin\Resources\UserFingerprintResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserFingerprints extends ListRecords
{
    protected static string $resource = UserFingerprintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
