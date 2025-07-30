<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserFingerprintResource\Pages;

use App\Filament\Admin\Resources\UserFingerprintResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserFingerprint extends EditRecord
{
    protected static string $resource = UserFingerprintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
