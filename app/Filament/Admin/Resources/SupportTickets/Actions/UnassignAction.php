<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Actions;

use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Override;

class UnassignAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-user-minus');
        $this->color('warning');
        $this->requiresConfirmation();
        $this->visible(fn (SupportTicket $record): bool => $record->assigned_to !== null);
        $this->successNotificationTitle('The ticket has been unassigned.');
        $this->action(function (SupportTicket $record, SupportTicketManager $supportTicketManager): void {
            $supportTicketManager->assignTicket($record);
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'unassign';
    }
}
