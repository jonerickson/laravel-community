<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTicketCategories\Pages;

use App\Filament\Admin\Resources\SupportTicketCategories\SupportTicketCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicketCategory extends EditRecord
{
    protected static string $resource = SupportTicketCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
