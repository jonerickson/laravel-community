<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Override;

class SyncGroupsAction extends Action
{
    protected Closure|User|null $user = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->color('gray');
        $this->label('Sync Groups');
        $this->icon(Heroicon::OutlinedUserGroup);
        $this->requiresConfirmation();
        $this->modalHeading('Sync Groups');
        $this->modalDescription('This will manually add and remove the user to all the groups they should belong to. Only the default admin and member groups will remain if previously assigned.');
        $this->modalSubmitActionLabel('Sync');
        $this->successNotificationTitle("The user's groups have been successfully synced.");
        $this->failureNotificationTitle("The user's groups could not be synced. Please try again later.");

        $this->action(function (SyncGroupsAction $action): void {
            if (! ($user = $action->getUser()) instanceof User) {
                $action->failure();

                return;
            }

            $user->syncGroups();

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'sync_groups';
    }

    public function user(User|Closure|null $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->evaluate($this->user);
    }
}
