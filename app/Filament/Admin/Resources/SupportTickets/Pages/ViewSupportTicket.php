<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Pages;

use App\Filament\Admin\Resources\SupportTickets\Actions\AssignToMeAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\CloseAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\ReopenAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\ResolveAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\UnassignAction;
use App\Filament\Admin\Resources\SupportTickets\SupportTicketResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ActionGroup::make([
                AssignToMeAction::make(),
                UnassignAction::make(),
                ResolveAction::make(),
                CloseAction::make(),
                ReopenAction::make(),
            ]),
        ];
    }
}
