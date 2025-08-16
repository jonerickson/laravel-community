<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Groups\Pages;

use App\Filament\Admin\Resources\Groups\GroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
