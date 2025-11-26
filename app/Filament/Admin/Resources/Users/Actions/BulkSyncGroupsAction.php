<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Users\CreateSyncGroupsBatchAction;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Override;

class BulkSyncGroupsAction extends BulkAction
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Sync selected users');
        $this->icon(Heroicon::OutlinedUserGroup);
        $this->color('gray');
        $this->requiresConfirmation();
        $this->modalHeading("Sync Selected User's Groups");
        $this->modalDescription('This will perform a group sync for each user. Are you sure you want to do this?');
        $this->modalSubmitActionLabel('Perform Sync');
        $this->successNotificationTitle("A background job has been dispatched to update the selected user's groups.");
        $this->action(function (BulkSyncGroupsAction $action, Collection $records): void {
            CreateSyncGroupsBatchAction::execute($records->pluck('id'));
            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulk_sync_users';
    }
}
