<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeBaseArticles\Pages;

use App\Filament\Admin\Resources\KnowledgeBaseArticles\KnowledgeBaseArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Override;

class EditKnowledgeBaseArticle extends EditRecord
{
    protected static string $resource = KnowledgeBaseArticleResource::class;

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return Str::limit($this->record?->excerpt);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
