<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyResource\Pages;

use App\Filament\Admin\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolicy extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
