<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Actions;

use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Override;

class AssignToMeAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Assign to Me');
        $this->icon('heroicon-o-user-plus');
        $this->color('info');
        $this->requiresConfirmation();
        $this->visible(fn (SupportTicket $record): bool => $record->assigned_to !== Auth::id());
        $this->successNotificationTitle('The ticket has been assigned to you.');
        $this->action(function (SupportTicket $record, SupportTicketManager $supportTicketManager): void {
            $supportTicketManager->assignTicket($record, Auth::id());
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'assign_to_me';
    }
}
