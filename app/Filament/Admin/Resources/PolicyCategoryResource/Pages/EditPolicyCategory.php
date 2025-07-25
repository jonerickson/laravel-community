<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyCategoryResource\Pages;

use App\Filament\Admin\Resources\PolicyCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolicyCategory extends EditRecord
{
    protected static string $resource = PolicyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
