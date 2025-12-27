<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Pages;

use App\Filament\Admin\Resources\SupportTickets\Actions\AssignToMeAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\CloseAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\ReopenAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\ResolveAction;
use App\Filament\Admin\Resources\SupportTickets\Actions\UnassignAction;
use App\Filament\Admin\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        /** @var SupportTicket $ticket */
        $ticket = $this->getRecord();

        return $ticket->subject;
    }

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
