<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTicketCategories\Pages;

use App\Filament\Admin\Resources\SupportTicketCategories\SupportTicketCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportTicketCategories extends ListRecords
{
    protected static string $resource = SupportTicketCategoryResource::class;

    protected ?string $subheading = 'The available support ticket categories';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
