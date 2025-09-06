<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ForumCategories\Pages;

use App\Filament\Admin\Resources\ForumCategories\ForumCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateForumCategory extends CreateRecord
{
    protected static string $resource = ForumCategoryResource::class;
}
