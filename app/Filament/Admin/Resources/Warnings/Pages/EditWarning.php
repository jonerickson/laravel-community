<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Warnings\Pages;

use App\Filament\Admin\Resources\Warnings\WarningResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarning extends EditRecord
{
    protected static string $resource = WarningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
