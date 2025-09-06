<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumCategories\Pages;

use App\Filament\Admin\Resources\ForumCategories\ForumCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListForumCategories extends ListRecords
{
    protected static string $resource = ForumCategoryResource::class;

    protected ?string $subheading = 'Manage your categories that are used to group similar forums.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
