<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Pages;

use App\Filament\Admin\Resources\SupportTickets\SupportTicketResource;
use App\Filament\Admin\Resources\SupportTickets\Widgets\SupportTicketStatsOverview;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected ?string $subheading = 'Manage your community support tickets.';

    protected function getHeaderActions(): array
    {
        return [];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            SupportTicketStatsOverview::class,
        ];
    }
}
