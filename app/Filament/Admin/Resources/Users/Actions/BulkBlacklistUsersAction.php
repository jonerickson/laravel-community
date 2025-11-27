<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Users\BlacklistUserAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Override;

class BulkBlacklistUsersAction extends BulkAction
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Blacklist selected users');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');
        $this->requiresConfirmation();
        $this->modalHeading('Blacklist Selected Users');
        $this->modalDescription('Are you sure you want to blacklist the selected users?');
        $this->successNotificationTitle('The users have been successfully blacklisted.');
        $this->schema([
            Textarea::make('reason')
                ->label('Reason')
                ->required()
                ->maxLength(1000),
        ]);
        $this->action(function (BulkBlacklistUsersAction $action, array $data, Collection $records): void {
            foreach ($records as $record) {
                BlacklistUserAction::execute($record, $data['reason']);
            }

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulk_blacklist_users';
    }
}
