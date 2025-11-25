<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Users\BanUserAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Override;

class BanAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Ban User');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');
        $this->visible(fn (User $record): bool => ! $record->is_banned && $record->fingerprints->count());
        $this->requiresConfirmation();
        $this->modalHeading('Ban User');
        $this->modalDescription('Are you sure you want to ban this user? They will be immediately logged out and unable to access the site.');
        $this->modalSubmitActionLabel('Ban User');
        $this->successNotificationTitle('The user has been successfully banned.');
        $this->schema([
            Textarea::make('ban_reason')
                ->label('Ban Reason')
                ->required()
                ->maxLength(1000),
        ]);
        $this->action(function (BanAction $action, User $record, array $data): void {
            BanUserAction::execute($record, $data['ban_reason']);
            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'ban_user';
    }
}
