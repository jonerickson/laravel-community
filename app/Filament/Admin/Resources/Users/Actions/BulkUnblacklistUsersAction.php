<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Users\UnblacklistUserAction;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Override;

class BulkUnblacklistUsersAction extends BulkAction
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Unblacklist selected users');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');
        $this->requiresConfirmation();
        $this->modalHeading('Unblacklist Selected Users');
        $this->modalDescription('Are you sure you want to remove the selected users from the blacklist?');
        $this->successNotificationTitle('The users have been successfully removed from the blacklist.');
        $this->action(function (BulkUnblacklistUsersAction $action, Collection $records): void {
            foreach ($records as $record) {
                UnblacklistUserAction::execute($record);
            }

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulk_unblacklist_users';
    }
}
