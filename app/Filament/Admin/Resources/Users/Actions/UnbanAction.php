<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Users\UnbanUserAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class UnbanAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Unban User');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');
        $this->visible(fn (User $record): bool => $record->is_banned && $record->fingerprints->count());
        $this->requiresConfirmation();
        $this->modalHeading('Unban User');
        $this->modalDescription('Are you sure you want to unban this user?');
        $this->modalSubmitActionLabel('Unban User');
        $this->successNotificationTitle('The user has been successfully unbanned.');
        $this->action(function (UnbanAction $action, User $record): void {
            UnbanUserAction::execute($record);
            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'unban_user';
    }
}
