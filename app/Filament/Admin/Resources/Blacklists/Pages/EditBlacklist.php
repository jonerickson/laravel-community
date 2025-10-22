<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blacklists\Pages;

use App\Filament\Admin\Resources\Blacklists\BlacklistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlacklist extends EditRecord
{
    protected static string $resource = BlacklistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
