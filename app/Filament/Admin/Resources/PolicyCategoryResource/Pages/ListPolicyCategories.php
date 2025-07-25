<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyCategoryResource\Pages;

use App\Filament\Admin\Resources\PolicyCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPolicyCategories extends ListRecords
{
    protected static string $resource = PolicyCategoryResource::class;

    protected ?string $subheading = 'Manage your organization policy categories.';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
