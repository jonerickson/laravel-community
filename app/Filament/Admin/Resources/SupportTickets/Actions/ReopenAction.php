<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Actions;

use App\Enums\SupportTicketStatus;
use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Override;

class ReopenAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-arrow-path');
        $this->color('warning');
        $this->requiresConfirmation();
        $this->visible(fn (SupportTicket $record): bool => $record->canTransitionTo(SupportTicketStatus::Open));
        $this->successNotificationTitle('The ticket has been reopened.');
        $this->action(function (SupportTicket $record, SupportTicketManager $supportTicketManager): void {
            $supportTicketManager->openTicket($record);
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'reopen';
    }
}
