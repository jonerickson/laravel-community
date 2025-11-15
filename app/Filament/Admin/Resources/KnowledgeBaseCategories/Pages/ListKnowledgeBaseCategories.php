<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeBaseCategories\Pages;

use App\Filament\Admin\Resources\KnowledgeBaseCategories\KnowledgeBaseCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeBaseCategories extends ListRecords
{
    protected static string $resource = KnowledgeBaseCategoryResource::class;

    protected ?string $subheading = 'Group your knowledge base articles by category.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
