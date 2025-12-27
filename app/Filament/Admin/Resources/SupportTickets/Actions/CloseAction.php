<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Actions;

use App\Enums\SupportTicketStatus;
use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Override;

class CloseAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-x-circle');
        $this->color('danger');
        $this->requiresConfirmation();
        $this->visible(fn (SupportTicket $record): bool => $record->canTransitionTo(SupportTicketStatus::Closed));
        $this->successNotificationTitle('The ticket has been closed.');
        $this->action(function (SupportTicket $record, SupportTicketManager $supportTicketManager): void {
            $supportTicketManager->closeTicket($record);
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'close';
    }
}
