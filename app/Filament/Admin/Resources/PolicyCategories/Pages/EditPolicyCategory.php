<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PolicyCategories\Pages;

use App\Filament\Admin\Resources\PolicyCategories\PolicyCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Override;

class EditPolicyCategory extends EditRecord
{
    protected static string $resource = PolicyCategoryResource::class;

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return Str::limit($this->record?->description);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
