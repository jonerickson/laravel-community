<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyCategories\Pages;

use App\Filament\Admin\Resources\PolicyCategories\PolicyCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPolicyCategory extends EditRecord
{
    protected static string $resource = PolicyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
