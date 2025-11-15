<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeBaseArticles\Pages;

use App\Filament\Admin\Resources\KnowledgeBaseArticles\KnowledgeBaseArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeBaseArticles extends ListRecords
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    protected ?string $subheading = 'Create troubleshooting, FAQ and guide documentation for your community.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
