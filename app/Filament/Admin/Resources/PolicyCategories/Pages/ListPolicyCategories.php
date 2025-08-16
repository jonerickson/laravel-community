<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyCategories\Pages;

use App\Filament\Admin\Resources\PolicyCategories\PolicyCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPolicyCategories extends ListRecords
{
    protected static string $resource = PolicyCategoryResource::class;

    protected ?string $subheading = 'Manage your organization policy categories.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
