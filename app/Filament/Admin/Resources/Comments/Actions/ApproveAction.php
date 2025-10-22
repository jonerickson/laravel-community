<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Comments\Actions;

use App\Models\Comment;
use Filament\Actions\Action;
use Override;

class ApproveAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-check');
        $this->color('success');
        $this->requiresConfirmation();
        $this->visible(fn (Comment $record): bool => ! $record->is_approved);
        $this->successNotificationTitle('The comment has been successfully approved.');
        $this->action(function (Comment $record): void {
            $record->approve();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'approve';
    }
}
