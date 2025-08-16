<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyCategories\Pages;

use App\Filament\Admin\Resources\PolicyCategories\PolicyCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePolicyCategory extends CreateRecord
{
    protected static string $resource = PolicyCategoryResource::class;
}
