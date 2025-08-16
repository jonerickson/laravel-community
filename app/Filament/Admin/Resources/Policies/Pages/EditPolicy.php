<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Policies\Pages;

use App\Filament\Admin\Resources\Policies\PolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPolicy extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
