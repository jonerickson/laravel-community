<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Users\BanUserAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Override;

class BulkBanUsersAction extends BulkAction
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Ban selected users');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');
        $this->requiresConfirmation();
        $this->modalHeading('Ban Selected Users');
        $this->modalDescription('Are you sure you want to ban the selected users?');
        $this->successNotificationTitle('The users have been successfully banned.');
        $this->schema([
            Textarea::make('ban_reason')
                ->label('Ban Reason')
                ->required()
                ->maxLength(1000),
        ]);
        $this->action(function (BulkBanUsersAction $action, array $data, Collection $records): void {
            foreach ($records as $record) {
                BanUserAction::execute($record, $data['ban_reason']);
            }

            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'bulk_ban_users';
    }
}
