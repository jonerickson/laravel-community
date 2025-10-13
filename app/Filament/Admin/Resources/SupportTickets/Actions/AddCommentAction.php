<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTickets\Actions;

use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use Closure;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Override;

class AddCommentAction extends Action
{
    protected Closure|SupportTicket|null $ticket = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Add comment');
        $this->icon('heroicon-o-chat-bubble-left-right');
        $this->color('info');
        $this->modalHeading('Add Comment to Ticket');
        $this->modalSubmitActionLabel('Add comment');
        $this->modalWidth(Width::ThreeExtraLarge);
        $this->schema([
            Forms\Components\RichEditor::make('content')
                ->label('Comment')
                ->required()
                ->placeholder('Enter your comment here...')
                ->columnSpanFull(),
        ]);
        $this->successNotificationTitle('Comment added successfully.');
        $this->action(function (array $data, SupportTicketManager $supportTicketManager): void {
            $supportTicketManager->addComment(
                ticket: $this->getTicket(),
                content: $data['content'],
                userId: Auth::id()
            );
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'add_comment';
    }

    public function supportTicket(Closure|SupportTicket|null $ticket): static
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getTicket(): ?SupportTicket
    {
        return $this->evaluate($this->ticket);
    }
}
