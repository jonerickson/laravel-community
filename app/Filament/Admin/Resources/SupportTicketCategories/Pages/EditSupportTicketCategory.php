<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTicketCategories\Pages;

use App\Filament\Admin\Resources\SupportTicketCategories\SupportTicketCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Override;

class EditSupportTicketCategory extends EditRecord
{
    protected static string $resource = SupportTicketCategoryResource::class;

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
