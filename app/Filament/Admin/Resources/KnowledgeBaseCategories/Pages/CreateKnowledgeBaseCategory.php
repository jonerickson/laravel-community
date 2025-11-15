<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeBaseCategories\Pages;

use App\Filament\Admin\Resources\KnowledgeBaseCategories\KnowledgeBaseCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeBaseCategory extends CreateRecord
{
    protected static string $resource = KnowledgeBaseCategoryResource::class;
}
