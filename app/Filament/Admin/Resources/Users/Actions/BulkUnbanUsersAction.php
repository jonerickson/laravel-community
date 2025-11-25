<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Users\UnbanUserAction;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Override;

class BulkUnbanUsersAction extends BulkAction
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Unban selected users');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');
        $this->requiresConfirmation();
        $this->modalHeading('Unban Selected Users');
        $this->modalDescription('Are you sure you want to unban the selected users?');
        $this->successNotificationTitle('The users have been successfully unbanned.');
        $this->action(function (BulkUnbanUsersAction $action, Collection $records): void {
            foreach ($records as $record) {
                UnbanUserAction::execute($record);
            }

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulk_unban_users';
    }
}
