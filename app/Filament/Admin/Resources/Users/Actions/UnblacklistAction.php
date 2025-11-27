<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Users\UnblacklistUserAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class UnblacklistAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Unblacklist User');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');
        $this->visible(fn (User $record): bool => $record->is_blacklisted);
        $this->requiresConfirmation();
        $this->modalHeading('Unblacklist User');
        $this->modalDescription('Are you sure you want to this user from the blacklist?');
        $this->modalSubmitActionLabel('Unblacklist User');
        $this->successNotificationTitle('The user has been successfully removed from the blacklist.');
        $this->action(function (UnblacklistAction $action, User $record): void {
            UnblacklistUserAction::execute($record);
            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'unblacklist_user';
    }
}
