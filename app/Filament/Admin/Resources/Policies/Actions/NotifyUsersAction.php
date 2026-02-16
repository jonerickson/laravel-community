<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Policies\Actions;

use App\Mail\Policies\PolicyUpdatedMail;
use App\Models\Policy;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
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
            User::query()
                ->whereNotNull('email')
                ->whereNotNull('email_verified_at')
                ->cursor()
                ->each(function (User $user) use ($record): void {
                    Mail::to($user)->queue(new PolicyUpdatedMail($record));
                });

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'notify-users';
    }
}
