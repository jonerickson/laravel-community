<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Actions;

use App\Enums\SupportTicketStatus;
use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Override;

class ResolveAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Resolve');
        $this->icon('heroicon-o-check-circle');
        $this->color('success');
        $this->requiresConfirmation();
        $this->visible(fn (SupportTicket $record): bool => $record->canTransitionTo(SupportTicketStatus::Resolved));
        $this->successNotificationTitle('The ticket has been resolved.');
        $this->action(function (SupportTicket $record, SupportTicketManager $supportTicketManager): void {
            $supportTicketManager->resolveTicket($record);
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'mark_resolved';
    }
}
