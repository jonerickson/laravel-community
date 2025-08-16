<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ProductCategories\Pages;

use App\Filament\Admin\Resources\ProductCategories\ProductCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductCategories extends ListRecords
{
    protected static string $resource = ProductCategoryResource::class;

    protected ?string $subheading = 'Manage your product categories.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
