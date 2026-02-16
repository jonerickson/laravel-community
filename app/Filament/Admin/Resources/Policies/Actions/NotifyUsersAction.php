<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Policies\Actions;

use App\Jobs\Policies\NotifyUsersOfPolicyUpdate;
use App\Models\Policy;
use Filament\Actions\Action;
use Override;

class NotifyUsersAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Notify Users');
        $this->color('warning');
        $this->requiresConfirmation();
        $this->modalHeading('Notify all users');
        $this->modalDescription('Are you sure you want to send an email notification about this policy update to all users? This action cannot be undone.');
        $this->modalSubmitActionLabel('Send notifications');
        $this->successNotificationTitle('Policy update notifications have been queued for all users.');
        $this->action(function (NotifyUsersAction $action, Policy $record): void {
            NotifyUsersOfPolicyUpdate::dispatch($record);

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'notify-users';
    }
}
