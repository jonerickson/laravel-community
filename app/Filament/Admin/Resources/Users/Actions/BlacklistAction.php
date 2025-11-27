<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Actions;

use App\Actions\Users\BlacklistUserAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Override;

class BlacklistAction extends Action
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Blacklist User');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->color('danger');
        $this->visible(fn (User $record): bool => ! $record->is_blacklisted);
        $this->requiresConfirmation();
        $this->modalHeading('Blacklist User');
        $this->modalDescription('Are you sure you want to blacklist this user? They will be immediately logged out and unable to access the site.');
        $this->modalSubmitActionLabel('Blacklist User');
        $this->successNotificationTitle('The user has been successfully blacklisted.');
        $this->schema([
            Textarea::make('reason')
                ->label('Reason')
                ->required()
                ->maxLength(1000),
        ]);
        $this->action(function (BlacklistAction $action, User $record, array $data): void {
            BlacklistUserAction::execute($record, $data['reason']);
            $action->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'blacklist_user';
    }
}
