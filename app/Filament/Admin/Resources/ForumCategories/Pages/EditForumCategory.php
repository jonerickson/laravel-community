<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumCategories\Pages;

use App\Filament\Admin\Resources\ForumCategories\ForumCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditForumCategory extends EditRecord
{
    protected static string $resource = ForumCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
